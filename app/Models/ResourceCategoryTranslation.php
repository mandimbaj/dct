<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceCategoryTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_resource_category_translation';

    public $timestamps = false;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'master_id', 'category_id');
    }
}
