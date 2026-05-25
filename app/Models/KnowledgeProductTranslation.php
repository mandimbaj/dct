<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeProductTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_knowledge_product_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(KnowledgeProduct::class, 'master_id', 'product_id');
    }
}
