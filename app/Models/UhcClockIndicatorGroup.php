<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UhcClockIndicatorGroup extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_uhclock_indicator_groups';

    protected $primaryKey = 'group_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(UhcClockIndicatorGroupTranslation::class, 'master_id', 'group_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(UhcClockIndicator::class, 'group_id', 'group_id');
    }

    public function themes(): HasMany
    {
        return $this->hasMany(UhcClockTheme::class, 'group_id', 'group_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}
