<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** A short update shown on the customer's Notifications page in their account. */
class AccountMessage extends Notification
{
    public function __construct(public string $title, public string $body, public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,string|null> */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
