<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UhcClockTheme extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_uhclock_indicator_themes';

    protected $primaryKey = 'domain_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(UhcClockThemeTranslation::class, 'master_id', 'domain_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UhcClockIndicatorGroup::class, 'group_id', 'group_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'domain_id');
    }

    public function indicators(): BelongsToMany
    {
        return $this->belongsToMany(
            UhcClockIndicator::class,
            'stg_uhclock_indicator_themes_indicators',
            'stguhcindicatortheme_id',
            'stguhclockindicators_id',
            'domain_id',
            'id',
        );
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName((string) $this->getKey());
    }
}
