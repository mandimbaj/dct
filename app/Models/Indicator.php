<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'afrocode', 'gen_code', 'reference_id'])]
class Indicator extends Model
{
    use HasFactory;
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_indicator';

    protected $primaryKey = 'indicator_id';

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
        static::creating(function (Indicator $indicator): void {
            $indicator->uuid ??= (string) Str::uuid();
        });
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(IndicatorReference::class, 'reference_id', 'reference_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(IndicatorTranslation::class, 'master_id', 'indicator_id');
    }

    public function indicatorValues(): HasMany
    {
        return $this->hasMany(HealthIndicatorValue::class, 'indicator_id', 'indicator_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->afrocode);
    }
}
