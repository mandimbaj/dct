<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthServiceProgrammeLookup extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'vw_hsc_indicators_lookup';

    protected $primaryKey = 'indicator_id';

    public $incrementing = false;

    protected $guarded = [];

    public $timestamps = false;
}
