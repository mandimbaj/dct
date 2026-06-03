<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeGroupTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_worldbank_incomegroups_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function incomeGroup(): BelongsTo
    {
        return $this->belongsTo(IncomeGroup::class, 'master_id', 'wb_income_groupid');
    }
}
