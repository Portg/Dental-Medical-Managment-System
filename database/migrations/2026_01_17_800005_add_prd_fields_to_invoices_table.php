<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 发票表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 患者ID (直接关联)
            if (!Schema::hasColumn('invoices', 'patient_id')) {
                $table->unsignedBigInteger('patient_id')->nullable()->after('appointment_id');
                $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            }

            // 病历ID
            if (!Schema::hasColumn('invoices', 'medical_case_id')) {
                $table->unsignedBigInteger('medical_case_id')->nullable()->after('patient_id');
                $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('set null');
            }

            // 发票日期
            if (!Schema::hasColumn('invoices', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('invoice_no');
            }

            // 小计 (折扣前)
            if (!Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('invoice_date');
            }

            // 折扣金额
            if (!Schema::hasColumn('invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 2)->default(0)->after('subtotal');
            }

            // 税额
            if (!Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0)->after('discount_amount');
            }

            // 总金额
            if (!Schema::hasColumn('invoices', 'total_amount')) {
                $table->decimal('total_amount', 14, 2)->default(0)->after('tax_amount');
            }

            // 已付金额
            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 14, 2)->default(0)->after('total_amount');
            }

            // 未付金额
            if (!Schema::hasColumn('invoices', 'outstanding_amount')) {
                $table->decimal('outstanding_amount', 14, 2)->default(0)->after('paid_amount');
            }

            // 支付状态
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded', 'overdue', 'written_off'])->default('unpaid')->after('outstanding_amount');
            }

            // 到期日
            if (!Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('payment_status');
            }

            // 发票类型
            if (!Schema::hasColumn('invoices', 'invoice_type')) {
                $table->enum('invoice_type', ['standard', 'insurance', 'estimate', 'proforma'])->default('standard')->after('due_date');
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
        Schema::table('invoices', function (Blueprint $table) {
            $foreignKeys = ['patient_id', 'medical_case_id'];
            foreach ($foreignKeys as $fk) {
                if (Schema::hasColumn('invoices', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }

            $columns = [
                'patient_id', 'medical_case_id', 'invoice_date',
                'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
                'paid_amount', 'outstanding_amount', 'payment_status',
                'due_date', 'invoice_type'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
