<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseAuthenticationUser extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'authentication_customuser';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_staff' => 'boolean',
            'is_superuser' => 'boolean',
            'last_login' => 'datetime',
            'date_joined' => 'datetime',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }
}
