<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);
        $request->user()->unreadNotifications->markAsRead();

        return view('front.commun.notifications.index', [
            'notifications' => $notifications,
            'layout' => $request->user()->hasRole('particulier') ? 'layouts.front' : 'layouts.back',
        ]);
    }
}
