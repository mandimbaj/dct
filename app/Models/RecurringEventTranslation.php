<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Translation row for RecurringEvent.
 *
 * The theme field lives here, not on stg_recurring_event.
 */
class RecurringEventTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_recurring_event_translation';

    public $timestamps = false;

    /**
     * Parent recurring event referenced by master_id.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(RecurringEvent::class, 'master_id', 'event_id');
    }
}
