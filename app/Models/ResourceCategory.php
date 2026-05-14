<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceCategory extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_resource_category';

    protected $primaryKey = 'category_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(ResourceCategoryTranslation::class, 'master_id', 'category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'type_id', 'type_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(KnowledgeProduct::class, 'categorization_id', 'category_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
