<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedImportRow extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'data_wizard_record';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    public function importRecord(): BelongsTo
    {
        return $this->belongsTo(ImportRecord::class, 'run_id');
    }
}
