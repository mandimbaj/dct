<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationDomainTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_publication_domain_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(PublicationDomain::class, 'master_id', 'domain_id');
    }
}
