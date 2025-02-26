<?php

namespace App\Event;

use App\Entity\Notification;
use Symfony\Contracts\EventDispatcher\Event;

class NotificationCreatedEvent extends Event
{
    public const NAME = 'notification.created';

    private Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }
}