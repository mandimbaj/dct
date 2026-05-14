<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataElementTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_data_element_translation';

    public $timestamps = false;

    public function dataElement(): BelongsTo
    {
        return $this->belongsTo(DataElement::class, 'master_id', 'dataelement_id');
    }
}
