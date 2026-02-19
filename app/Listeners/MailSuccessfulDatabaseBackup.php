<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Mail;
use Spatie\Backup\Events\BackupZipWasCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MailSuccessfulDatabaseBackup
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param BackupZipWasCreated $event
     * @return void
     */
    public function handle(BackupZipWasCreated $event)
    {
        $this->mailBackupFile($event->pathToZip);
    }

    public function mailBackupFile($path)
    {
        try {
            Mail::raw(__('emails.new_database_backup_file'), function ($message) use ($path) {
                $message->to(config('app.db_backup_email'))
                    ->subject(__('emails.db_backup_done_subject'))
                    ->attach($path);
            });
        } catch (\Exception $exception) {
            throw $exception;
        }

    }
}
