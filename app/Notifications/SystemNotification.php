<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi di dalam aplikasi (ikon lonceng), disimpan di tabel `notifications`.
 *
 * color: amber = menunggu tindakan, gas = berhasil/disetujui, flame = ditolak/peringatan.
 */
class SystemNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $icon = 'bell',
        public string $color = 'amber',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'color' => $this->color,
        ];
    }
}
