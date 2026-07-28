<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PendingTaskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $module,
        public string $title,
        public string $message,
        public ?string $url = null,
        public array $meta = [],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'module'  => $this->module,
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
            'meta'    => $this->meta,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
