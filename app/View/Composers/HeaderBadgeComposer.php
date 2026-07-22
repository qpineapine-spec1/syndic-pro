<?php

namespace App\View\Composers;

use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HeaderBadgeComposer
{
    public function composeMessages(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            $view->with('messages', collect());
            return;
        }

        $unread = Message::unreadForUser($user)->orderByDesc('created_at')->get();

        $preview = $unread->take(5)->map(function ($m) {
            return [
                'subject' => $m->subject ?: 'Nouveau message',
                'body' => $m->body,
                'time' => $m->created_at->diffForHumans(),
                'read' => false,
            ];
        });

        $view->with('messages', $preview)->with('messagesUnreadTotal', $unread->count());
    }

    public function composeNotifications(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            $view->with('notifications', collect());
            return;
        }

        $unread = Notification::forUser($user)->where('is_read', false)->orderByDesc('created_at')->get();

        $preview = $unread->take(5)->map(function ($n) {
            return [
                'title' => $n->title,
                'message' => $n->message,
                'time' => $n->created_at->diffForHumans(),
                'read' => false,
            ];
        });

        $view->with('notifications', $preview)->with('notificationsUnreadTotal', $unread->count());
    }
}