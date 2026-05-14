<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'shortname', 'description', 'master_id'])]
class CategoryOptionTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_categoryoption_translation';

    public $timestamps = false;

    public function categoryOption(): BelongsTo
    {
        return $this->belongsTo(IndicatorCategory::class, 'master_id', 'categoryoption_id');
    }
}
