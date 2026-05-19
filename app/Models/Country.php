<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'code', 'iso_alpha', 'iso_number', 'parent_id', 'locationlevel_id', 'special_id', 'wb_income_id'])]
class Country extends Model
{
    use HasFactory;
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_location';

    protected $primaryKey = 'location_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Country $country): void {
            if (blank($country->uuid)) {
                $country->uuid = (string) Str::uuid();
            }
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LocationTranslation::class, 'master_id', 'location_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'location_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'location_id');
    }

    public function locationLevel(): BelongsTo
    {
        return $this->belongsTo(LocationLevel::class, 'locationlevel_id', 'locationlevel_id');
    }

    public function indicatorValues(): HasMany
    {
        return $this->hasMany(HealthIndicatorValue::class, 'location_id', 'location_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
