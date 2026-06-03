<?php

namespace App\Observers;

use App\Support\AdminActivityNotifier;
use Illuminate\Database\Eloquent\Model;

class AdminActivityObserver
{
    public function created(Model $model): void
    {
        AdminActivityNotifier::record('created', $model);
    }

    public function updated(Model $model): void
    {
        AdminActivityNotifier::record('updated', $model);
    }

    public function deleted(Model $model): void
    {
        AdminActivityNotifier::record('deleted', $model);
    }

    public function restored(Model $model): void
    {
        AdminActivityNotifier::record('restored', $model);
    }

    public function forceDeleted(Model $model): void
    {
        AdminActivityNotifier::record('force_deleted', $model);
    }
}
