<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'code', 'level', 'parent_id'])]
class IndicatorDomain extends Model
{
    use HasFactory;
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_indicator_domain';

    protected $primaryKey = 'domain_id';

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
        static::creating(function (IndicatorDomain $indicatorDomain): void {
            GeneratedCode::ensureUuid($indicatorDomain);
            GeneratedCode::ensure($indicatorDomain, 'code', 'DOM', 45);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'domain_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'domain_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(IndicatorDomainTranslation::class, 'master_id', 'domain_id');
    }

    public function indicators(): BelongsToMany
    {
        return $this->belongsToMany(
            Indicator::class,
            'stg_indicator_domain_members',
            'stgindicatordomain_id',
            'stgindicator_id',
            'domain_id',
            'indicator_id',
        )->withPivot('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
