<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications =
            $request->user()
                ->userNotifications()
                ->latest()
                ->paginate(20);

        $items =
            $notifications
                ->getCollection()
                ->map(function ($notification) {
                    return [
                        'id' =>
                            $notification->id,

                        'type' =>
                            $notification
                                ->type
                                ->value,

                        'title' =>
                            $notification->title,

                        'message' =>
                            $notification->message,

                        'data' =>
                            $notification->data,

                        'is_read' =>
                            $notification
                                ->read_at !== null,

                        'read_at' =>
                            $notification
                                ->read_at
                                ?->toISOString(),

                        'created_at' =>
                            $notification
                                ->created_at
                                ->toISOString(),
                    ];
                });

        return ApiResponse::success(
            'تم جلب الإشعارات بنجاح.',
            [
                'notifications' => $items,

                'pagination' => [
                    'current_page' => $notifications->currentPage(),

                    'last_page' => $notifications->lastPage(),

                    'per_page' => $notifications->perPage(),

                    'total' => $notifications->total(),
                ],
            ]
        );
    }

    public function unreadCount(Request $request)
    {
        $count =
            $request->user()
                ->userNotifications()
                ->whereNull('read_at')
                ->count();

        return ApiResponse::success(
            'تم جلب عدد الإشعارات غير المقروءة بنجاح.',
            [
                'unread_count' => $count,
            ]
        );
    }

    public function markAsRead( Request $request, int $id) 
    {
        $notification =
                $request->user()
                        ->userNotifications()
                        ->findOrFail($id);

        if ($notification->read_at === null) 
        {
            $notification->update([
                'read_at' => now(),
                ]);
        }

        return ApiResponse::success(
            'تم تعليم الإشعار كمقروء بنجاح.'
        );
    }
}