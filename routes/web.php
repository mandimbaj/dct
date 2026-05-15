<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/admin', function () {
    $country = optional(optional(auth()->user())->location)->iso_alpha;

    return redirect('/admin/'.($country ? strtolower(substr(trim((string) $country), 0, 2)) : 'global'));
});

Route::redirect('/admin/login', '/admin/global/login');
Route::redirect('/admin/logout', '/admin/global/logout');

Route::get('/admin/{country}/notifications/{notification}', NotificationController::class)
    ->name('admin.notifications.show');

Route::match(['get', 'post'], '/locale', LocaleController::class)->name('locale.switch');

Route::get('/repository/{path}', function (string $path) {
    $repositoryRoot = realpath(base_path('../_reference/aho-stage-datacapture/aho-stage-datacapture-main/repository'));
    abort_unless($repositoryRoot, 404);

    $filePath = realpath($repositoryRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));

    abort_unless($filePath && str_starts_with($filePath, $repositoryRoot) && is_file($filePath), 404);

    return Response::file($filePath);
})
    ->where('path', '.*')
    ->name('repository.file');
