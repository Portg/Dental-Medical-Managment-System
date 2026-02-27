<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('member_levels', function (Blueprint $table) {
            $table->decimal('opening_fee', 10, 2)->default(0)->after('is_active');
            $table->decimal('min_initial_deposit', 10, 2)->default(0)->after('opening_fee');
            $table->json('deposit_bonus_rules')->nullable()->after('min_initial_deposit');
            $table->decimal('referral_points', 10, 2)->default(0)->after('deposit_bonus_rules');
            $table->json('payment_method_points_rates')->nullable()->after('referral_points');
        });
    }

    public function down()
    {
        Schema::table('member_levels', function (Blueprint $table) {
            $table->dropColumn([
                'opening_fee',
                'min_initial_deposit',
                'deposit_bonus_rules',
                'referral_points',
                'payment_method_points_rates',
            ]);
        });
    }
};
