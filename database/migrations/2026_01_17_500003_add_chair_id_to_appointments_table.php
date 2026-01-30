<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChairIdToAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'chair_id')) {
                $table->unsignedBigInteger('chair_id')->nullable()->after('doctor_id');
                $table->foreign('chair_id')->references('id')->on('chairs');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'chair_id')) {
                $table->dropForeign(['chair_id']);
                $table->dropColumn('chair_id');
            }
        });
    }
}
