<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToTreatmentPlansTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 治疗方案表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('treatment_plans', function (Blueprint $table) {
            // 关联牙位 (JSON数组)
            if (!Schema::hasColumn('treatment_plans', 'related_teeth')) {
                $table->json('related_teeth')->nullable()->after('plan_name');
            }

            // 总价
            if (!Schema::hasColumn('treatment_plans', 'total_price')) {
                $table->decimal('total_price', 14, 2)->default(0)->after('estimated_cost');
            }

            // 折扣率
            if (!Schema::hasColumn('treatment_plans', 'discount_rate')) {
                $table->decimal('discount_rate', 5, 2)->default(0)->after('total_price');
            }

            // 最终价格
            if (!Schema::hasColumn('treatment_plans', 'final_price')) {
                $table->decimal('final_price', 14, 2)->default(0)->after('discount_rate');
            }

            // 确认人 (医生)
            if (!Schema::hasColumn('treatment_plans', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('final_price');
                $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            }

            // 确认时间
            if (!Schema::hasColumn('treatment_plans', 'confirmed_at')) {
                $table->dateTime('confirmed_at')->nullable()->after('confirmed_by');
            }

            // 患者电子签名
            if (!Schema::hasColumn('treatment_plans', 'electronic_signature')) {
                $table->text('electronic_signature')->nullable()->after('confirmed_at');
            }

            // 风险告知
            if (!Schema::hasColumn('treatment_plans', 'risk_disclosure')) {
                $table->text('risk_disclosure')->nullable()->after('electronic_signature');
            }

            // 审批状态
            if (!Schema::hasColumn('treatment_plans', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected', 'revision_needed'])->default('pending')->after('status');
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
        Schema::table('treatment_plans', function (Blueprint $table) {
            if (Schema::hasColumn('treatment_plans', 'confirmed_by')) {
                $table->dropForeign(['confirmed_by']);
            }

            $columns = [
                'related_teeth', 'total_price', 'discount_rate', 'final_price',
                'confirmed_by', 'confirmed_at', 'electronic_signature',
                'risk_disclosure', 'approval_status'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('treatment_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
