<?php

namespace App\Contracts;

interface Notifiable
{
    public function notifications();

    public function readNotifications();

    public function unreadNotifications();

    public function notify($instance);

    public function notifyNow($instance, array $channels = null);

    public function routeNotificationFor($driver);
}