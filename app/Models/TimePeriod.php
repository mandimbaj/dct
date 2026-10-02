<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'name', 'shortname', 'code', 'description'])]
class TimePeriod extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_periodicity_type';

    protected $primaryKey = 'period_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected static function booted(): void
    {
        static::creating(function (TimePeriod $timePeriod): void {
            GeneratedCode::ensureUuid($timePeriod);
            GeneratedCode::ensure($timePeriod, 'code', 'PER', 50);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TimePeriodTranslation::class, 'master_id', 'period_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->name ?: $this->code);
    }
}
