<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/** Notifikasi lonceng milik pengguna yang sedang login. */
class NotificationController extends Controller
{
    /** Halaman semua notifikasi, atau JSON untuk dropdown lonceng (dipanggil berkala). */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json(self::feed($user));
        }

        return view('notifications.index', [
            'notifications' => $user->notifications()->paginate(20),
        ]);
    }

    /** Tandai dibaca lalu buka halaman yang dituju notifikasi. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(self::feed($request->user()))
            : back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /** @return array{unread: int, items: array<int, array<string, mixed>>} */
    public static function feed($user): array
    {
        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(8)->get()->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'color' => $n->data['color'] ?? 'amber',
                'icon' => $n->data['icon'] ?? 'bell',
                'url' => route('notifications.open', $n->id),
                'time' => $n->created_at->diffForHumans(),
                'read' => $n->read_at !== null,
            ])->all(),
        ];
    }
}
