<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRecord extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'data_wizard_run';

    protected $guarded = [];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(FailedImportRow::class, 'run_id');
    }
}
