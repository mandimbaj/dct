<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Translation row for EventAnnouncement.
 *
 * The message field is translated, so table views read it through EventAnnouncement.display_message.
 */
class EventAnnouncementTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_event_announcement_translation';

    public $timestamps = false;

    /**
     * Parent announcement referenced by master_id.
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(EventAnnouncement::class, 'master_id', 'event_id');
    }
}
