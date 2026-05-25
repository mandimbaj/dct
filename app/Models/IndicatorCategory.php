<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'code', 'category_id'])]
class IndicatorCategory extends Model
{
    use HasFactory;
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_categoryoption';

    protected $primaryKey = 'categoryoption_id';

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
        static::creating(function (IndicatorCategory $indicatorCategory): void {
            GeneratedCode::ensureUuid($indicatorCategory);
            GeneratedCode::ensure($indicatorCategory, 'code', 'CAT', 50);
        });
    }

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(CategoryParent::class, 'category_id', 'category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryOptionTranslation::class, 'master_id', 'categoryoption_id');
    }

    public function indicatorValues(): HasMany
    {
        return $this->hasMany(HealthIndicatorValue::class, 'categoryoption_id', 'categoryoption_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
