<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMembershipFieldsToPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('member_no')->nullable()->after('patient_no');
            $table->unsignedBigInteger('member_level_id')->nullable()->after('member_no');
            $table->decimal('member_balance', 10, 2)->default(0)->after('member_level_id');
            $table->decimal('member_points', 10, 2)->default(0)->after('member_balance');
            $table->decimal('total_consumption', 10, 2)->default(0)->after('member_points');
            $table->date('member_since')->nullable()->after('total_consumption');
            $table->date('member_expiry')->nullable()->after('member_since');
            $table->enum('member_status', ['Active', 'Inactive', 'Expired'])->default('Inactive')->after('member_expiry');

            $table->foreign('member_level_id')->references('id')->on('member_levels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['member_level_id']);
            $table->dropColumn([
                'member_no',
                'member_level_id',
                'member_balance',
                'member_points',
                'total_consumption',
                'member_since',
                'member_expiry',
                'member_status'
            ]);
        });
    }
}
