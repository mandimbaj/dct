<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MicrosoftEntraController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/admin', function () {
    $country = optional(optional(auth()->user())->location)->iso_alpha;

    return redirect('/admin/'.($country ? strtolower(substr(trim((string) $country), 0, 2)) : 'af'));
});

Route::redirect('/admin/login', '/admin/af/login');
Route::redirect('/admin/logout', '/admin/af/logout');
Route::redirect('/admin/global', '/admin/af');
Route::redirect('/admin/global/login', '/admin/af/login');
Route::redirect('/admin/global/logout', '/admin/af/logout');

Route::get('/admin/{country}/microsoft/login', [MicrosoftEntraController::class, 'redirectToProvider'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('microsoft-entra.redirect');

Route::get('/auth/microsoft/callback', [MicrosoftEntraController::class, 'callback'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('microsoft-entra.callback');

Route::get('/admin/{country}/notifications/{notification}', NotificationController::class)
    ->name('admin.notifications.show');

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/repository/{path}', function (string $path) {
    $repositoryRoot = realpath(base_path('../_reference/aho-stage-datacapture/aho-stage-datacapture-main/repository'));
    abort_unless($repositoryRoot, 404);

    $filePath = realpath($repositoryRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));

    abort_unless($filePath && str_starts_with($filePath, $repositoryRoot) && is_file($filePath), 404);

    return Response::file($filePath);
})
    ->where('path', '.*')
    ->name('repository.file');
