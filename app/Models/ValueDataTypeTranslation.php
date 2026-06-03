<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValueDataTypeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_value_datatype_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function valueDataType(): BelongsTo
    {
        return $this->belongsTo(ValueDataType::class, 'master_id', 'valuetype_id');
    }
}
