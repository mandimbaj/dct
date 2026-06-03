<?php

namespace App\Http\Controllers;

use App\Support\TopbarAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __invoke(Request $request, string $country, DatabaseNotification $notification): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/admin/'.($country ?: 'af').'/login');
        }

        abort_unless(
            $notification->notifiable_type === $user->getMorphClass()
            && (string) $notification->notifiable_id === (string) $user->getKey(),
            404,
        );

        $data = $notification->data;
        $title = $data['title'] ?? __('aho.notifications.read.title');
        $body = $data['body'] ?? $data['message'] ?? '';

        if (blank($notification->read_at)) {
            $notification->markAsRead();
            TopbarAlerts::forgetForUser($user, $country);
        }

        return view('notifications.show', [
            'backUrl' => URL::to('/admin/'.($country ?: 'af')),
            'body' => $body,
            'notification' => $notification,
            'title' => $title,
        ]);
    }
}
