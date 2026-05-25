<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'code'])]
class LocationLevel extends Model
{
    use HasFactory;
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_location_level';

    protected $primaryKey = 'locationlevel_id';

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
        static::creating(function (LocationLevel $locationLevel): void {
            GeneratedCode::ensureUuid($locationLevel);
            GeneratedCode::ensure($locationLevel, 'code', 'LL', 50);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LocationLevelTranslation::class, 'master_id', 'locationlevel_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Country::class, 'locationlevel_id', 'locationlevel_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
