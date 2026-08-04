<?php

namespace App\Models;

use App\Support\TextEncoding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HealthFacility extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_health_facility';

    protected $primaryKey = 'facility_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (HealthFacility $facility): void {
            if (blank($facility->uuid)) {
                $facility->uuid = (string) Str::uuid();
            }

            $facility->user_id ??= auth()->id() ?? 1;
            $facility->status ??= 'active';

            if (blank($facility->code)) {
                $facility->code = 'HF-'.strtoupper(Str::substr(str_replace('-', '', (string) $facility->uuid), 0, 10));
            }
        });

        static::saving(function (HealthFacility $facility): void {
            $facility->phone_code ??= '';
            $facility->phone_part ??= '';

            if (filled($facility->phone_part)) {
                $facility->phone_number = trim((string) $facility->phone_code.(string) $facility->phone_part) ?: null;
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(FacilityOwner::class, 'owner_id', 'owner_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class, 'type_id', 'type_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouseUploadedBy(): BelongsTo
    {
        return $this->belongsTo(WarehouseAuthenticationUser::class, 'user_id', 'id');
    }

    public function serviceAvailabilities(): HasMany
    {
        return $this->hasMany(FacilityServiceAvailability::class, 'facility_id', 'facility_id');
    }

    public function serviceCapacities(): HasMany
    {
        return $this->hasMany(FacilityServiceCapacity::class, 'facility_id', 'facility_id');
    }

    public function serviceReadiness(): HasMany
    {
        return $this->hasMany(FacilityServiceReadiness::class, 'facility_id', 'facility_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->name ?: ($this->shortname ?: ($this->code ?: (string) $this->getKey()));

        return TextEncoding::clean($name) ?? $name;
    }
}
