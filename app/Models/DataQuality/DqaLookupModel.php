<?php

namespace App\Models\DataQuality;

use Illuminate\Database\Eloquent\Model;

abstract class DqaLookupModel extends Model
{
    protected $connection = 'warehouse';

    protected $guarded = [];

    public $timestamps = false;
}
