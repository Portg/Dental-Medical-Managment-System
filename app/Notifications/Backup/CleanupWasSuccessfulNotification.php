<?php

namespace App\Notifications\Backup;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Backup\Events\CleanupWasSuccessful;

class CleanupWasSuccessfulNotification extends BaseBackupNotification
{
    public function __construct(CleanupWasSuccessful $event)
    {
        $this->applicationName = static::resolveApplicationName();
        $this->diskName = $event->backupDestination->diskName();
        $this->destinationProperties = static::resolveDestinationProperties($event->backupDestination);
    }

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->from(config('backup.notifications.mail.from.address', config('mail.from.address')), config('backup.notifications.mail.from.name', config('mail.from.name')))
            ->subject(trans('backup::notifications.cleanup_successful_subject', ['application_name' => $this->applicationName]))
            ->line(trans('backup::notifications.cleanup_successful_body', ['application_name' => $this->applicationName, 'disk_name' => $this->diskName]));

        foreach ($this->destinationProperties as $name => $value) {
            $mailMessage->line("{$name}: {$value}");
        }

        return $mailMessage;
    }
}
