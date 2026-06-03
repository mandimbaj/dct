<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingInstitution extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_traininginstitution';

    protected $primaryKey = 'institution_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(TrainingInstitutionTranslation::class, 'master_id', 'institution_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    /**
     * Institution type reference exposed in the new Health workforce Institution types submenu.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class, 'type_id', 'type_id');
    }

    /**
     * Programmes offered by the institution through the legacy Django pivot table.
     */
    public function programmes(): BelongsToMany
    {
        return $this->belongsToMany(
            InstitutionProgramme::class,
            'stg_institution_programs_lookup',
            'stgtraininginstitution_id',
            'stginstitutionprogrammes_id',
            'institution_id',
            'course_id',
        )->withPivot('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
