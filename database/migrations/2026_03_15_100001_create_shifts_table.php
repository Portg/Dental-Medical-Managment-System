<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('work_status', ['on_duty', 'rest'])->default('on_duty');
            $table->string('color', 7)->default('#409EFF');
            $table->integer('sort_order')->default(0);
            $table->integer('max_patients')->default(1);
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('_who_added')->references('id')->on('users');
        });

        // Seed default shifts（测试环境无用户时跳过，避免 FK 约束失败）
        $adminId = DB::table('users')->value('id');
        if ($adminId === null) {
            return;
        }

        DB::table('shifts')->insert([
            [
                'name'        => '上午班',
                'start_time'  => '08:00',
                'end_time'    => '12:00',
                'work_status' => 'on_duty',
                'color'       => '#F56C6C',
                'sort_order'  => 1,
                'max_patients' => 8,
                '_who_added'  => $adminId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => '下午班',
                'start_time'  => '13:30',
                'end_time'    => '18:00',
                'work_status' => 'on_duty',
                'color'       => '#409EFF',
                'sort_order'  => 2,
                'max_patients' => 8,
                '_who_added'  => $adminId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => '全天班',
                'start_time'  => '08:00',
                'end_time'    => '18:00',
                'work_status' => 'on_duty',
                'color'       => '#67C23A',
                'sort_order'  => 3,
                'max_patients' => 15,
                '_who_added'  => $adminId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => '休息',
                'start_time'  => '00:00',
                'end_time'    => '00:00',
                'work_status' => 'rest',
                'color'       => '#909399',
                'sort_order'  => 4,
                'max_patients' => 0,
                '_who_added'  => $adminId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
