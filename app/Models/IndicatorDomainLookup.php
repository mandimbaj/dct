<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicatorDomainLookup extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'aho_domain_lookup';

    protected $primaryKey = 'indicator_id';

    public $incrementing = false;

    protected $guarded = [];

    public $timestamps = false;
}
