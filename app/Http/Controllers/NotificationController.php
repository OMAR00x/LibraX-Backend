<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TestNotification;
use App\Jobs\SendNotificationJob;
use App\Services\JobService;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'تم وضع علامة مقروء']);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم وضع علامة مقروء على الكل']);
    }

    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    public function sendTest(Request $request)
    {
        $user = Auth::user();

        $title = $request->input('title', 'إشعار تجريبي');
        $message = $request->input('message', 'هذا إشعار تجريبي من النظام');

        // إرسال باستخدام JobService
        JobService::sendNotificationToUser(
            $user->id,
            $title,
            $message,
            ['type' => 'test']
        );

        return response()->json(['message' => 'تم إرسال الإشعار']);
    }

    public function delete($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'تم حذف الإشعار']);
    }

    public function deleteAll()
    {
        $user = Auth::user();
        $user->notifications()->delete();

        return response()->json(['message' => 'تم حذف جميع الإشعارات']);
    }


}
