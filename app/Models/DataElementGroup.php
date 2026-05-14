<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataElementGroup extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_data_element_group';

    protected $primaryKey = 'group_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(DataElementGroupTranslation::class, 'master_id', 'group_id');
    }

    public function dataElements(): BelongsToMany
    {
        return $this->belongsToMany(
            DataElement::class,
            'stg_data_element_membership',
            'stgdataelementgroup_id',
            'stgdataelement_id',
            'group_id',
            'dataelement_id',
        );
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
