<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\GeneralNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $locale = $request->header('Accept-Language', 'ar');
        if (!in_array($locale, ['ar', 'en'])) {
            $locale = 'ar';
        }

        $notifications = $user->notifications()->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));

        $formatted = collect($notifications->items())->map(function ($notification) use ($locale) {
            $data = $notification->data;
            return [
                'id' => $notification->id,
                'user_id' => $notification->notifiable_id,
                'title' => $locale === 'en' ? ($data['title_en'] ?? $data['title'] ?? '') : ($data['title_ar'] ?? $data['title'] ?? ''),
                'message' => $locale === 'en' ? ($data['message_en'] ?? $data['message'] ?? '') : ($data['message_ar'] ?? $data['message'] ?? ''),
                'type' => $data['type'] ?? 'SYSTEM',
                'is_read' => !is_null($notification->read_at),
                'created_at' => $notification->created_at->toISOString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'notifications' => $formatted,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ]
            ]
        ]);
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'تم وضع علامة مقروء',
            'message_en' => 'Marked as read successfully'
        ]);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'تم وضع علامة مقروء على الكل',
            'message_en' => 'Marked all as read successfully'
        ]);
    }

    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => $count
            ]
        ]);
    }

    public function sendTest(Request $request)
    {
        $user = Auth::user();

        $title = $request->input('title', 'إشعار تجريبي');
        $message = $request->input('message', 'هذا إشعار تجريبي من النظام');
        $type = $request->input('type', 'SYSTEM');

        $user->notify(new GeneralNotification(
            $type,
            $title,
            $title,
            $message,
            $message,
            ['test' => true]
        ));

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الإشعار التجريبي بنجاح',
            'message_en' => 'Test notification sent successfully'
        ]);
    }

    public function delete($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الإشعار',
            'message_en' => 'Notification deleted successfully'
        ]);
    }

    public function deleteAll()
    {
        $user = Auth::user();
        $user->notifications()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف جميع الإشعارات',
            'message_en' => 'All notifications deleted successfully'
        ]);
    }
}
