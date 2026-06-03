<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Recurring workforce event from stg_recurring_event.
 *
 * The Filament submenu is labelled Nursing and midwifery to match the older Django admin
 * wording, but the model remains generic because the warehouse table is generic.
 */
class RecurringEvent extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_recurring_event';

    protected $primaryKey = 'event_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    /**
     * Multilingual event labels and themes from stg_recurring_event_translation.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(RecurringEventTranslation::class, 'master_id', 'event_id');
    }

    /**
     * Country/location that owns this event.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    /**
     * Health cadres associated with the recurring event through the legacy Django pivot.
     */
    public function cadres(): BelongsToMany
    {
        return $this->belongsToMany(
            HealthCadre::class,
            'stg_recurring_event_lookup',
            'stgrecurringevent_id',
            'stghealthcadre_id',
            'event_id',
            'cadre_id',
        )->withPivot('id');
    }

    /**
     * Display label resolved in the active warehouse language, with the code as fallback.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }

    /**
     * Optional short label from the translation table.
     */
    public function getDisplayShortnameAttribute(): ?string
    {
        return $this->preferredTranslationValue('shortname');
    }

    /**
     * Event theme text from translations, shown as a compact table column.
     */
    public function getDisplayThemeAttribute(): ?string
    {
        return $this->preferredTranslationValue('theme');
    }
}
