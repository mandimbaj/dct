<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Translation row for InstitutionProgramme.
 *
 * Each row stores one language version of name, shortname and description.
 */
class InstitutionProgrammeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_institution_programme_translation';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Parent training programme referenced by master_id.
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(InstitutionProgramme::class, 'master_id', 'course_id');
    }
}
