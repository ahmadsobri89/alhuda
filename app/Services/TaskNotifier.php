<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PendingTaskNotification;
use Illuminate\Support\Facades\Notification;

class TaskNotifier
{
    /**
     * Notify every user holding the given role that a task is pending their action.
     */
    public static function notifyRole(string $role, string $module, string $title, string $message, ?string $url = null, array $meta = []): void
    {
        $users = User::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereJsonContains('roles', $role)->orWhere('role', $role))
            ->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new PendingTaskNotification($module, $title, $message, $url, $meta));
        }
    }
}
