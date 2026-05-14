<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicationDomain extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_publication_domain';

    protected $primaryKey = 'domain_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(PublicationDomainTranslation::class, 'master_id', 'domain_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'domain_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeProduct::class,
            'stg_product_domain_members',
            'stgproductdomain_id',
            'stgknowledgeproduct_id',
            'domain_id',
            'product_id',
        )->withPivot('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
