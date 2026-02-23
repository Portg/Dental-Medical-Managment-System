<?php

namespace App\Notifications\Backup;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Backup\Events\CleanupHasFailed;

class CleanupHasFailedNotification extends BaseBackupNotification
{
    protected string $exceptionMessage;
    protected string $exceptionTrace;

    public function __construct(CleanupHasFailed $event)
    {
        $this->applicationName = static::resolveApplicationName();
        $this->exceptionMessage = $event->exception->getMessage();
        $this->exceptionTrace = mb_substr($event->exception->getTraceAsString(), 0, 4000);
        $this->diskName = $event->backupDestination?->diskName() ?? '';
        $this->destinationProperties = static::resolveDestinationProperties($event->backupDestination);
    }

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->error()
            ->from(config('backup.notifications.mail.from.address', config('mail.from.address')), config('backup.notifications.mail.from.name', config('mail.from.name')))
            ->subject(trans('backup::notifications.cleanup_failed_subject', ['application_name' => $this->applicationName]))
            ->line(trans('backup::notifications.cleanup_failed_body', ['application_name' => $this->applicationName]))
            ->line(trans('backup::notifications.exception_message', ['message' => $this->exceptionMessage]))
            ->line(trans('backup::notifications.exception_trace', ['trace' => $this->exceptionTrace]));

        foreach ($this->destinationProperties as $name => $value) {
            $mailMessage->line("{$name}: {$value}");
        }

        return $mailMessage;
    }
}
