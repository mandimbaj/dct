<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UhcClockThemeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_uhclock_indicator_themes_translation';

    public $timestamps = false;

    public function theme(): BelongsTo
    {
        return $this->belongsTo(UhcClockTheme::class, 'master_id', 'domain_id');
    }
}
