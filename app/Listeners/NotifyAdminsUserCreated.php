<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Models\User;
use App\Notifications\NewUserNotification;

class NotifyAdminsUserCreated
{
    public function handle(UserCreated $event): void
    {
        $admins = User::where('role', 'admin')->where('id', '!=', $event->user->id)->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewUserNotification($event->user));
        }
    }
}
