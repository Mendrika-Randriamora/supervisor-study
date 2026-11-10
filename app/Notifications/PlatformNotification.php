<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PlatformNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $icon = 'bi-bell',
        public string $type = 'info',
        public ?string $key = null,
        public array $extra = [],
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'type' => $this->type,
            'key' => $this->key,
        ], $this->extra);
    }
}
