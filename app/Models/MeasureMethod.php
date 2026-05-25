<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'code'])]
class MeasureMethod extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_measuremethod';

    protected $primaryKey = 'measuremethod_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected static function booted(): void
    {
        static::creating(function (MeasureMethod $measureMethod): void {
            GeneratedCode::ensureUuid($measureMethod);
            GeneratedCode::ensure($measureMethod, 'code', 'MM', 50);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MeasureMethodTranslation::class, 'master_id', 'measuremethod_id');
    }

    public function indicatorValues(): HasMany
    {
        return $this->hasMany(HealthIndicatorValue::class, 'measuremethod_id', 'measuremethod_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
