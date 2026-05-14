<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['uuid', 'name', 'shortname', 'code', 'description'])]
class TimePeriod extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_periodicity_type';

    protected $primaryKey = 'period_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->code;
    }
}
