<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('member_password')->nullable()->after('member_status');
            $table->unsignedBigInteger('referred_by')->nullable()->after('member_password');

            $table->foreign('referred_by')->references('id')->on('patients')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['member_password', 'referred_by']);
        });
    }
};
