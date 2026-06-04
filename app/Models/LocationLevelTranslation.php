<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'type', 'name', 'description', 'master_id'])]
class LocationLevelTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_location_level_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function locationLevel(): BelongsTo
    {
        return $this->belongsTo(LocationLevel::class, 'master_id', 'locationlevel_id');
    }
}
