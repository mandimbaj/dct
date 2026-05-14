<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataElementGroupTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_data_element_group_translation';

    public $timestamps = false;

    public function dataElementGroup(): BelongsTo
    {
        return $this->belongsTo(DataElementGroup::class, 'master_id', 'group_id');
    }
}
