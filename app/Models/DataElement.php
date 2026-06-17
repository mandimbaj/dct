<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataElement extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_data_element';

    protected $primaryKey = 'dataelement_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected static function booted(): void
    {
        static::creating(function (DataElement $dataElement): void {
            GeneratedCode::ensureUuid($dataElement);
            GeneratedCode::ensure($dataElement, 'code', 'DE', 45);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(DataElementTranslation::class, 'master_id', 'dataelement_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            DataElementGroup::class,
            'stg_data_element_membership',
            'stgdataelement_id',
            'stgdataelementgroup_id',
            'dataelement_id',
            'group_id',
        );
    }

    public function values(): HasMany
    {
        return $this->hasMany(DataElementValue::class, 'dataelement_id', 'dataelement_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
