<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'shortname', 'description', 'level', 'master_id'])]
class DataSourceTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_datasource_translation';

    public $timestamps = false;

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class, 'master_id', 'datasource_id');
    }
}
