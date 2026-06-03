<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Institution type reference row from the warehouse table stg_institution_type.
 *
 * Django exposed this as StgInstitutionType; Laravel uses it for the Health workforce
 * Institution types submenu and for the TrainingInstitution.type relationship.
 */
class InstitutionType extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_institution_type';

    protected $primaryKey = 'type_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    /**
     * Multilingual labels stored separately in stg_institution_type_translation.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(InstitutionTypeTranslation::class, 'master_id', 'type_id');
    }

    /**
     * Training institutions using this institution type.
     */
    public function trainingInstitutions(): HasMany
    {
        return $this->hasMany(TrainingInstitution::class, 'type_id', 'type_id');
    }

    /**
     * Display label resolved in the active warehouse language, with the code as fallback.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }

    /**
     * Short label resolved from the same translation priority as the display name.
     */
    public function getDisplayShortnameAttribute(): ?string
    {
        return $this->preferredTranslationValue('shortname');
    }
}
