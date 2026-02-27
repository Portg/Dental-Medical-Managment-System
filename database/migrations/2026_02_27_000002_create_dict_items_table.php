<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dict_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();   // 字典类型，如 patient_group
            $table->string('code', 50);             // 存储值
            $table->string('name', 100);            // 显示名称
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'code']);
        });

        // 迁入现有 patient_group 枚举值
        $groups = [
            ['type' => 'patient_group', 'code' => 'walk_in',      'name' => '直客',   'sort_order' => 1],
            ['type' => 'patient_group', 'code' => 'referral',     'name' => '转介绍', 'sort_order' => 2],
            ['type' => 'patient_group', 'code' => 'orthodontics', 'name' => '正畸',   'sort_order' => 3],
            ['type' => 'patient_group', 'code' => 'implant',      'name' => '种植',   'sort_order' => 4],
            ['type' => 'patient_group', 'code' => 'pediatric',    'name' => '儿童',   'sort_order' => 5],
        ];

        $now = now();
        foreach ($groups as &$g) {
            $g['created_at'] = $now;
            $g['updated_at'] = $now;
        }
        DB::table('dict_items')->insert($groups);
    }

    public function down(): void
    {
        Schema::dropIfExists('dict_items');
    }
};
