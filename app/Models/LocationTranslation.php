<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'description', 'latitude', 'longitude', 'cordinate', 'master_id'])]
class LocationTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_location_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'master_id', 'location_id');
    }
}
