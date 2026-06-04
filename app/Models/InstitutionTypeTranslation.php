<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Translation row for InstitutionType.
 *
 * Warehouse translation tables do not maintain their own created/updated timestamps.
 */
class InstitutionTypeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_institution_type_translation';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Parent institution type referenced by master_id.
     */
    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class, 'master_id', 'type_id');
    }
}
