<?php

namespace App\Models;

use App\Support\ApprovalWorkflow;
use App\Support\GeneratedCode;
use App\Support\PublicationFileStorage;
use App\Support\TextEncoding;
use App\Support\WarehouseLocale;
use App\Support\WarehouseUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class KnowledgeProduct extends Model
{
    private const INTERNAL_FILE_BASE_URL = 'https://afahobckpstorageaccount.blob.core.windows.net/afahobckpcontainer/production/files/';

    protected $connection = 'warehouse';

    protected $table = 'stg_knowledge_product';

    protected $primaryKey = 'product_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KnowledgeProduct $product): void {
            GeneratedCode::ensureUuid($product);
            GeneratedCode::ensure($product, 'code', 'KP', 45);
            $product->user_id ??= WarehouseUser::id();
            ApprovalWorkflow::syncStatus($product, $product->comment);
        });

        static::updating(function (KnowledgeProduct $product): void {
            if ($product->isDirty(['comment', 'approval_status', 'approved_by', 'approved_at'])) {
                ApprovalWorkflow::syncStatus($product, $product->comment);

                return;
            }

            if ($product->isDirty()) {
                ApprovalWorkflow::markPending($product);
            }
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(KnowledgeProductTranslation::class, 'master_id', 'product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'type_id', 'type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'categorization_id', 'category_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(
            PublicationDomain::class,
            'stg_product_domain_members',
            'stgknowledgeproduct_id',
            'stgproductdomain_id',
            'product_id',
            'domain_id',
        )->withPivot('id');
    }

    public function getDisplayTitleAttribute(): string
    {
        $translation = $this->preferredTranslation('title');

        return TextEncoding::clean($translation?->title) ?? $this->code;
    }

    public function getDisplayAuthorAttribute(): ?string
    {
        return TextEncoding::clean($this->preferredTranslation('author')?->author);
    }

    public function getDisplayYearAttribute(): ?int
    {
        return $this->preferredTranslation('year_published')?->year_published;
    }

    public function getPublicationFileUrlAttribute(): ?string
    {
        $translation = $this->preferredTranslation('internal_url')
            ?? $this->preferredTranslation('external_url');
        $path = $translation?->internal_url ?: $translation?->external_url;

        if (! filled($path)) {
            return null;
        }

        return self::publicationFileUrl($path);
    }

    public function getPublicationFileLabelAttribute(): ?string
    {
        $translation = $this->preferredTranslation('internal_url')
            ?? $this->preferredTranslation('external_url');

        if (filled($translation?->internal_url)) {
            return basename(str_replace('\\', '/', $translation->internal_url));
        }

        if (filled($translation?->external_url)) {
            return parse_url($translation->external_url, PHP_URL_HOST) ?: __('aho.fields.external_url');
        }

        return null;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        $coverImage = $this->preferredTranslation('cover_image')?->cover_image;

        if (! filled($coverImage)) {
            return null;
        }

        return PublicationFileStorage::url($coverImage);
    }

    private static function publicationFileUrl(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }

        $path = ltrim($path, '/');

        if ($publicUrl = self::publicUploadUrl($path)) {
            return $publicUrl;
        }

        if (str_contains($path, '/production/files/')) {
            $path = substr($path, strrpos($path, '/production/files/') + strlen('/production/files/'));
        }

        foreach (['production/files/', 'production/files'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = ltrim(substr($path, strlen($prefix)), '/');

                break;
            }
        }

        return rtrim(self::INTERNAL_FILE_BASE_URL, '/').'/'.str_replace(' ', '%20', $path);
    }

    private static function publicUploadUrl(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function preferredTranslation(?string $field = null): ?KnowledgeProductTranslation
    {
        $languages = WarehouseLocale::preferredLanguages();

        $translations = $this->translations
            ->whereIn('language_code', $languages)
            ->sortBy(fn ($translation): int => array_search($translation->language_code, $languages, true));

        if ($field !== null) {
            $preferred = $translations
                ->filter(fn ($translation): bool => filled($translation->{$field}))
                ->first();

            if ($preferred) {
                return $preferred;
            }
        }

        return $translations->first() ?? $this->translations->first();
    }
}
