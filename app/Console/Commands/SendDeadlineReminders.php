<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-deadline-reminders')]
#[Description('Envoie les rappels internes pour les échéances des 48 prochaines heures')]
class SendDeadlineReminders extends Command
{
    public function handle(NotificationService $notifications): int
    {
        $sent = $notifications->deadlineReminders();

        $this->info($sent.' rappel(s) d’échéance envoyé(s).');

        return self::SUCCESS;
    }
}
