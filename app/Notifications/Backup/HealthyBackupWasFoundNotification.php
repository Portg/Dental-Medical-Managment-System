<?php

namespace App\Notifications\Backup;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Backup\Events\HealthyBackupWasFound;

class HealthyBackupWasFoundNotification extends BaseBackupNotification
{
    public function __construct(HealthyBackupWasFound $event)
    {
        $this->applicationName = static::resolveApplicationName();
        $backupDestination = $event->backupDestinationStatus->backupDestination();
        $this->diskName = $backupDestination->diskName();
        $this->destinationProperties = static::resolveDestinationProperties($backupDestination);
    }

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->from(config('backup.notifications.mail.from.address', config('mail.from.address')), config('backup.notifications.mail.from.name', config('mail.from.name')))
            ->subject(trans('backup::notifications.healthy_backup_found_subject', ['application_name' => $this->applicationName, 'disk_name' => $this->diskName]))
            ->line(trans('backup::notifications.healthy_backup_found_body', ['application_name' => $this->applicationName]));

        foreach ($this->destinationProperties as $name => $value) {
            $mailMessage->line("{$name}: {$value}");
        }

        return $mailMessage;
    }
}
