<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UhcClockFact extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'vw_uhc_fact_data_indicators';

    protected $primaryKey = 'fact_id';

    public $incrementing = false;

    protected $guarded = [];

    public $timestamps = false;
}
