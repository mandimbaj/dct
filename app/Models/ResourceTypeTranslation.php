<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceTypeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_resource_type_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'master_id', 'type_id');
    }
}
