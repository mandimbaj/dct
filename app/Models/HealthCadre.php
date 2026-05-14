<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCadre extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_health_cadre';

    protected $primaryKey = 'cadre_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'cadre_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HealthCadreTranslation::class, 'master_id', 'cadre_id');
    }

    public function workforceValues(): HasMany
    {
        return $this->hasMany(HealthWorkforceValue::class, 'cadre_id', 'cadre_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
