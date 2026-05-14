<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_name',
    'user_email',
    'is_super_admin',
    'location_id',
    'country_iso',
    'country_name',
    'country_route',
    'method',
    'path',
    'full_url',
    'route_name',
    'page_label',
    'ip_address',
    'user_agent',
    'visited_at',
])]
class UserPageVisit extends Model
{
    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }
}
