<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportRecord extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'data_wizard_filesource';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
