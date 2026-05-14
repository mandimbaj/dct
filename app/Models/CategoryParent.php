<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'code'])]
class CategoryParent extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_category_parent';

    protected $primaryKey = 'category_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function categoryOptions(): HasMany
    {
        return $this->hasMany(IndicatorCategory::class, 'category_id', 'category_id');
    }
}
