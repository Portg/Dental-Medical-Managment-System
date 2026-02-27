<?php

namespace App\Console\Commands;

use App\MemberSetting;
use App\MemberTransaction;
use App\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireMemberPoints extends Command
{
    protected $signature = 'member:expire-points {--dry-run : Show what would be expired without making changes}';

    protected $description = 'Expire member points that have passed their expiry date';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $expiryDays = (int) MemberSetting::get('points_expiry_days', 0);

        if ($expiryDays === 0) {
            $this->info('Points expiry is disabled (points_expiry_days = 0). Nothing to do.');
            return 0;
        }

        // Find points transactions that have expired and haven't been reversed yet
        $expiredTransactions = MemberTransaction::whereNotNull('points_expires_at')
            ->where('points_expires_at', '<', now()->toDateString())
            ->where('transaction_type', 'Points')
            ->where('points_change', '>', 0)
            ->whereNull('deleted_at')
            ->get();

        if ($expiredTransactions->isEmpty()) {
            $this->info('No expired points transactions found.');
            return 0;
        }

        // Group by patient to batch-process
        $grouped = $expiredTransactions->groupBy('patient_id');
        $totalExpired = 0;
        $patientsAffected = 0;

        foreach ($grouped as $patientId => $transactions) {
            $totalPointsToExpire = $transactions->sum('points_change');

            if ($totalPointsToExpire <= 0) continue;

            $patient = Patient::find($patientId);
            if (!$patient) continue;

            // Only expire up to the points the patient actually has
            $actualExpiry = min($totalPointsToExpire, max(0, $patient->member_points ?? 0));

            if ($actualExpiry <= 0) continue;

            if ($dryRun) {
                $this->line("  [DRY RUN] Patient #{$patientId} ({$patient->full_name}): would expire {$actualExpiry} points");
            } else {
                DB::transaction(function () use ($patient, $actualExpiry, $transactions) {
                    $patient->member_points = max(0, ($patient->member_points ?? 0) - $actualExpiry);
                    $patient->save();

                    MemberTransaction::create([
                        'transaction_no'   => MemberTransaction::generateTransactionNo(),
                        'transaction_type' => 'Points',
                        'patient_id'       => $patient->id,
                        'amount'           => 0,
                        'balance_before'   => $patient->member_balance,
                        'balance_after'    => $patient->member_balance,
                        'points_change'    => -$actualExpiry,
                        'description'      => __('members.points_expired'),
                        '_who_added'       => null,
                    ]);

                    // Soft-delete the original expired transactions to prevent re-processing
                    MemberTransaction::whereIn('id', $transactions->pluck('id'))
                        ->update(['deleted_at' => now()]);
                });

                $this->line("  Patient #{$patientId} ({$patient->full_name}): expired {$actualExpiry} points");
            }

            $totalExpired += $actualExpiry;
            $patientsAffected++;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. Expired {$totalExpired} points across {$patientsAffected} patients.");

        return 0;
    }
}
