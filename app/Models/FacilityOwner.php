<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityOwner extends Model
{
    use HasPreferredTranslationName;

    public const GLOBAL_LOCATION_ID = 1;

    protected $connection = 'warehouse';

    protected $table = 'stg_facility_owner';

    protected $primaryKey = 'owner_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected static function booted(): void
    {
        static::saving(function (FacilityOwner $owner): void {
            GeneratedCode::ensureUuid($owner);
            GeneratedCode::ensure($owner, 'code', 'FO', 50);
            $owner->location_id = self::GLOBAL_LOCATION_ID;
            $owner->user_id ??= auth()->id() ?? 1;
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(FacilityOwnerTranslation::class, 'master_id', 'owner_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(HealthFacility::class, 'owner_id', 'owner_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
