<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceAndNotesToPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->after('insurance_company_id');
            $table->text('medication_history')->nullable()->after('address');
            $table->text('notes')->nullable()->after('medication_history');

            $table->foreign('source_id')->references('id')->on('patient_sources');
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
            $table->dropForeign(['source_id']);
            $table->dropColumn(['source_id', 'medication_history', 'notes']);
        });
    }
}
