<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomIconTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_fontawesome_icons_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function customIcon(): BelongsTo
    {
        return $this->belongsTo(CustomIcon::class, 'master_id', 'icon_id');
    }
}
