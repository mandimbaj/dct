<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class NationalObservatory extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_national_observatory';

    protected $primaryKey = 'observatory_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (NationalObservatory $observatory): void {
            GeneratedCode::ensureUuid($observatory);
            GeneratedCode::ensure($observatory, 'code', 'NHO', 45);
            $observatory->user_id ??= auth()->id() ?? 1;
        });

        static::saving(function (NationalObservatory $observatory): void {
            self::ensureSingleObservatoryPerCountry($observatory);

            $observatory->phone_code = self::phoneCodeForLocation($observatory->location_id) ?: $observatory->phone_code;
            $observatory->phone_number = self::phoneNumber($observatory->phone_code, $observatory->phone_part);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NationalObservatoryTranslation::class, 'master_id', 'observatory_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function locationCode(): BelongsTo
    {
        return $this->belongsTo(LocationCode::class, 'location_id', 'location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }

    public function getShortNameAttribute(): ?string
    {
        return $this->preferredTranslationValue('shortname');
    }

    public static function phoneCodeForLocation(mixed $locationId): ?string
    {
        if (blank($locationId)) {
            return null;
        }

        return LocationCode::query()
            ->where('location_id', $locationId)
            ->value('country_code');
    }

    public static function phoneNumber(?string $phoneCode, ?string $phonePart): ?string
    {
        $phoneCode = trim((string) $phoneCode);
        $phonePart = preg_replace('/\D+/', '', (string) $phonePart);

        if ($phoneCode === '' || $phonePart === '') {
            return null;
        }

        return $phoneCode.$phonePart;
    }

    public static function hasObservatoryForLocation(mixed $locationId, mixed $ignoreObservatoryId = null): bool
    {
        if (blank($locationId)) {
            return false;
        }

        return self::query()
            ->where('location_id', $locationId)
            ->when(filled($ignoreObservatoryId), fn ($query) => $query->where('observatory_id', '!=', $ignoreObservatoryId))
            ->exists();
    }

    public static function existingForLocation(mixed $locationId, mixed $ignoreObservatoryId = null): ?self
    {
        if (blank($locationId)) {
            return null;
        }

        return self::query()
            ->where('location_id', $locationId)
            ->when(filled($ignoreObservatoryId), fn ($query) => $query->where('observatory_id', '!=', $ignoreObservatoryId))
            ->first();
    }

    private static function ensureSingleObservatoryPerCountry(NationalObservatory $observatory): void
    {
        if (! self::hasObservatoryForLocation($observatory->location_id, $observatory->getKey())) {
            return;
        }

        throw ValidationException::withMessages([
            'location_id' => __('aho.validation.national_observatory_country_unique'),
        ]);
    }
}
