<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('member_transactions', function (Blueprint $table) {
            $table->date('points_expires_at')->nullable()->after('description');
            $table->decimal('bonus_amount', 10, 2)->default(0)->after('points_expires_at');
        });
    }

    public function down()
    {
        Schema::table('member_transactions', function (Blueprint $table) {
            $table->dropColumn(['points_expires_at', 'bonus_amount']);
        });
    }
};
