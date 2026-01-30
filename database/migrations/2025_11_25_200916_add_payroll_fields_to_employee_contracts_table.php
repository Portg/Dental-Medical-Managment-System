<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPayrollFieldsToEmployeeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            // 添加 contract_length 字段(如果不存在)
            if (!Schema::hasColumn('employee_contracts', 'contract_length')) {
                $table->integer('contract_length');
            }
            // 添加 contract_period 字段(如果不存在)
            if (!Schema::hasColumn('employee_contracts', 'contract_period')) {
                $table->enum('contract_period', ['Months', 'Years'])->after('contract_length');
            }

            // 添加 payroll_type 字段
            if (!Schema::hasColumn('employee_contracts', 'payroll_type')) {
                $table->enum('payroll_type', ['Salary', 'Commission'])->after('contract_period');
            }

            // 添加 gross_salary 字段
            if (!Schema::hasColumn('employee_contracts', 'gross_salary')) {
                $table->decimal('gross_salary', 15, 2)->nullable()->after('payroll_type');
            }

            // 添加 commission_percentage 字段
            if (!Schema::hasColumn('employee_contracts', 'commission_percentage')) {
                $table->decimal('commission_percentage', 5, 2)->nullable()->after('gross_salary');
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
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'contract_length',
                'contract_period',
                'payroll_type',
                'gross_salary',
                'commission_percentage'
            ]);
        });
    }
}
