<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Seed default settings
        $now = now();
        DB::table('member_settings')->insert([
            ['key' => 'points_enabled',         'value' => '1',   'type' => 'boolean', 'description' => '积分功能全局开关',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'points_expiry_days',     'value' => '0',   'type' => 'integer', 'description' => '积分有效天数（0=永不过期）', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'card_number_mode',       'value' => 'auto','type' => 'string',  'description' => '卡号生成模式 auto/phone/manual', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'referral_bonus_enabled', 'value' => '0',   'type' => 'boolean', 'description' => '推荐开卡功能开关',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'points_exchange_rate',   'value' => '100', 'type' => 'integer', 'description' => '积分兑换比例（X积分=1元）',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'points_exchange_enabled','value' => '1',   'type' => 'boolean', 'description' => '积分兑换功能开关',         'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('member_settings');
    }
};
