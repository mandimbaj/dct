<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeResourceTag extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_knowledge_resource_tag';

    protected $primaryKey = 'tag_id';

    protected $guarded = [];

    public $timestamps = false;

    public function publication(): BelongsTo
    {
        return $this->belongsTo(KnowledgeProduct::class, 'publications_id', 'product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }
}
