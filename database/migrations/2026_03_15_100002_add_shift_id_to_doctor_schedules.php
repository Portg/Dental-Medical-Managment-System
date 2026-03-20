<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('doctor_id');
            $table->string('recurring_group_id', 36)->nullable()->after('recurring_until');

            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->index('recurring_group_id');
        });

        // Migrate existing schedules: match to closest shift or create one
        $this->migrateExistingSchedules();

        // Make old columns nullable (keep for backward compatibility)
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            $table->integer('max_patients')->nullable()->change();
        });
    }

    private function migrateExistingSchedules(): void
    {
        $schedules = DB::table('doctor_schedules')
            ->whereNull('shift_id')
            ->whereNull('deleted_at')
            ->get();

        if ($schedules->isEmpty()) {
            return;
        }

        // Group by unique (start_time, end_time, max_patients) combos
        $combos = $schedules->groupBy(function ($s) {
            return $s->start_time . '|' . $s->end_time . '|' . $s->max_patients;
        });

        $adminId = DB::table('users')->value('id') ?? 1;
        $sortOrder = 10;

        foreach ($combos as $key => $group) {
            [$startTime, $endTime, $maxPatients] = explode('|', $key);

            // Try to find a matching existing shift
            $shiftId = DB::table('shifts')
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->whereNull('deleted_at')
                ->value('id');

            if (!$shiftId) {
                // Create a new shift for this combo
                $shiftId = DB::table('shifts')->insertGetId([
                    'name'         => sprintf('班次 %s-%s', substr($startTime, 0, 5), substr($endTime, 0, 5)),
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'work_status'  => 'on_duty',
                    'color'        => '#409EFF',
                    'sort_order'   => $sortOrder++,
                    'max_patients' => (int) $maxPatients,
                    '_who_added'   => $adminId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Update all schedules in this group
            $ids = $group->pluck('id')->toArray();
            DB::table('doctor_schedules')
                ->whereIn('id', $ids)
                ->update(['shift_id' => $shiftId]);
        }
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['recurring_group_id']);
            $table->dropColumn(['shift_id', 'recurring_group_id']);
        });
    }
};
