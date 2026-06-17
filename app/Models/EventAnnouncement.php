<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Workforce event announcement from stg_event_announcement.
 *
 * The table uses event_id as its primary key, following the old Django model rather than a
 * Laravel-style id column.
 */
class EventAnnouncement extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_event_announcement';

    protected $primaryKey = 'event_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected static function booted(): void
    {
        static::creating(function (EventAnnouncement $announcement): void {
            GeneratedCode::ensureUuid($announcement);
            GeneratedCode::ensure($announcement, 'code', 'ANN', 45);
            $announcement->status ??= 'active';
        });

        static::saving(function (EventAnnouncement $announcement): void {
            if (filled($announcement->start_year) && filled($announcement->end_year)) {
                $announcement->period = ((int) $announcement->start_year === (int) $announcement->end_year)
                    ? (string) (int) $announcement->start_year
                    : ((int) $announcement->start_year).'-'.((int) $announcement->end_year);
            }
        });
    }

    /**
     * Multilingual announcement title and message rows.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(EventAnnouncementTranslation::class, 'master_id', 'event_id');
    }

    /**
     * Country/location that owns this announcement.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    /**
     * Display title resolved in the active warehouse language, with the code as fallback.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }

    /**
     * Optional short title from the translation table.
     */
    public function getDisplayShortnameAttribute(): ?string
    {
        return $this->preferredTranslationValue('shortname');
    }

    /**
     * Announcement body text from translations, shown in the table.
     */
    public function getDisplayMessageAttribute(): ?string
    {
        return $this->preferredTranslationValue('message');
    }
}
