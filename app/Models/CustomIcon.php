<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomIcon extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_fontawesome_icons';

    protected $primaryKey = 'icon_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CustomIconTranslation::class, 'master_id', 'icon_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->unicode);
    }
}
