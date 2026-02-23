<?php

namespace App\Notifications\Backup;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Backup\Events\UnhealthyBackupWasFound;

class UnhealthyBackupWasFoundNotification extends BaseBackupNotification
{
    protected string $problemDescription;
    protected bool $wasUnexpected;
    protected string $healthCheckName;
    protected string $exceptionMessage;
    protected string $exceptionTrace;

    public function __construct(UnhealthyBackupWasFound $event)
    {
        $this->applicationName = static::resolveApplicationName();
        $backupDestination = $event->backupDestinationStatus->backupDestination();
        $this->diskName = $backupDestination->diskName();
        $this->destinationProperties = static::resolveDestinationProperties($backupDestination);

        $failure = $event->backupDestinationStatus->getHealthCheckFailure();
        $this->wasUnexpected = $failure?->wasUnexpected() ?? false;

        if ($this->wasUnexpected) {
            $this->problemDescription = trans('backup::notifications.unhealthy_backup_found_unknown');
            $this->healthCheckName = $failure->healthCheck()->name();
            $this->exceptionMessage = $failure->exception()->getMessage();
            $this->exceptionTrace = mb_substr($failure->exception()->getTraceAsString(), 0, 4000);
        } else {
            $this->problemDescription = $failure?->exception()?->getMessage() ?? '';
            $this->healthCheckName = '';
            $this->exceptionMessage = '';
            $this->exceptionTrace = '';
        }
    }

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->error()
            ->from(config('backup.notifications.mail.from.address', config('mail.from.address')), config('backup.notifications.mail.from.name', config('mail.from.name')))
            ->subject(trans('backup::notifications.unhealthy_backup_found_subject', ['application_name' => $this->applicationName]))
            ->line(trans('backup::notifications.unhealthy_backup_found_body', ['application_name' => $this->applicationName, 'disk_name' => $this->diskName]))
            ->line($this->problemDescription);

        foreach ($this->destinationProperties as $name => $value) {
            $mailMessage->line("{$name}: {$value}");
        }

        if ($this->wasUnexpected) {
            $mailMessage
                ->line('Health check: ' . $this->healthCheckName)
                ->line(trans('backup::notifications.exception_message', ['message' => $this->exceptionMessage]))
                ->line(trans('backup::notifications.exception_trace', ['trace' => $this->exceptionTrace]));
        }

        return $mailMessage;
    }
}
