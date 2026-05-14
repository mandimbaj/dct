<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'measure_value', 'description', 'master_id'])]
class MeasureMethodTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_measuremethod_translation';

    public $timestamps = false;

    public function measureMethod(): BelongsTo
    {
        return $this->belongsTo(MeasureMethod::class, 'master_id', 'measuremethod_id');
    }
}
