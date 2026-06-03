<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryParentTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_category_parent_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function categoryParent(): BelongsTo
    {
        return $this->belongsTo(CategoryParent::class, 'master_id', 'category_id');
    }
}
