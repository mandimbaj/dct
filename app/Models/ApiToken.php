<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id',
    'name',
    'token_prefix',
    'token_hash',
    'abilities',
    'last_used_at',
    'expires_at',
    'revoked_at',
])]
#[Hidden(['token_hash'])]
class ApiToken extends Model
{
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return blank($this->revoked_at)
            && (blank($this->expires_at) || $this->expires_at->isFuture());
    }

    public function markUsed(): void
    {
        if ($this->last_used_at instanceof Carbon && $this->last_used_at->diffInMinutes(now()) < 10) {
            return;
        }

        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
