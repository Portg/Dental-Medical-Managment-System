<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDemographicFieldsToPatientsTable extends Migration
{
    /**
     * Run the migrations.
     * 添加人口统计学字段：民族、婚姻状况、教育程度、血型
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            // 民族
            if (!Schema::hasColumn('patients', 'ethnicity')) {
                $table->string('ethnicity', 50)->nullable()->after('gender')->comment('民族');
            }

            // 婚姻状况
            if (!Schema::hasColumn('patients', 'marital_status')) {
                $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'other'])
                    ->nullable()->after('ethnicity')->comment('婚姻状况');
            }

            // 教育程度
            if (!Schema::hasColumn('patients', 'education')) {
                $table->enum('education', ['primary', 'junior_high', 'senior_high', 'college', 'bachelor', 'master', 'doctor', 'other'])
                    ->nullable()->after('marital_status')->comment('教育程度');
            }

            // 血型
            if (!Schema::hasColumn('patients', 'blood_type')) {
                $table->string('blood_type', 10)->nullable()->after('education')->comment('血型');
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
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'ethnicity')) {
                $table->dropColumn('ethnicity');
            }
            if (Schema::hasColumn('patients', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
            if (Schema::hasColumn('patients', 'education')) {
                $table->dropColumn('education');
            }
            if (Schema::hasColumn('patients', 'blood_type')) {
                $table->dropColumn('blood_type');
            }
        });
    }
}
