<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'code'])]
class DataSource extends Model
{
    use HasFactory;
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_datasource';

    protected $primaryKey = 'datasource_id';

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
        static::creating(function (DataSource $dataSource): void {
            GeneratedCode::ensureUuid($dataSource);
            GeneratedCode::ensure($dataSource, 'code', 'SRC', 50);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(DataSourceTranslation::class, 'master_id', 'datasource_id');
    }

    public function indicatorValues(): HasMany
    {
        return $this->hasMany(HealthIndicatorValue::class, 'datasource_id', 'datasource_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
