<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'shortname', 'description', 'master_id'])]
class IndicatorDomainTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_indicator_domain_translation';

    public $timestamps = false;

    public function domain(): BelongsTo
    {
        return $this->belongsTo(IndicatorDomain::class, 'master_id', 'domain_id');
    }
}
