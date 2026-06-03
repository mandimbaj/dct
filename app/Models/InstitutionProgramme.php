<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Training programme reference row from stg_institution_programme.
 *
 * The original Django model name was StgInstitutionProgrammes. The plural spelling remains
 * visible in pivot column names inherited from that application.
 */
class InstitutionProgramme extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_institution_programme';

    protected $primaryKey = 'course_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    /**
     * Multilingual programme labels from stg_institution_programme_translation.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(InstitutionProgrammeTranslation::class, 'master_id', 'course_id');
    }

    /**
     * Institutions offering this programme.
     *
     * The pivot table and column names mirror the old Django schema.
     */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(
            TrainingInstitution::class,
            'stg_institution_programs_lookup',
            'stginstitutionprogrammes_id',
            'stgtraininginstitution_id',
            'course_id',
            'institution_id',
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
}
