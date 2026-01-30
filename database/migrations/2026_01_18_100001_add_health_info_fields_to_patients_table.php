<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHealthInfoFieldsToPatientsTable extends Migration
{
    /**
     * Run the migrations.
     * 表单设计规范 F-PAT-001: 健康信息组字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            // 药物过敏 (JSON格式，存储多选值)
            // 预设选项：青霉素/头孢/磺胺/麻醉药/碘/乳胶 + 其他
            if (!Schema::hasColumn('patients', 'drug_allergies')) {
                $table->json('drug_allergies')->nullable()->after('medication_history');
            }

            // 药物过敏其他说明
            if (!Schema::hasColumn('patients', 'drug_allergies_other')) {
                $table->string('drug_allergies_other', 200)->nullable()->after('drug_allergies');
            }

            // 全身病史其他说明
            if (!Schema::hasColumn('patients', 'systemic_diseases_other')) {
                $table->string('systemic_diseases_other', 200)->nullable()->after('systemic_diseases');
            }

            // 当前用药
            if (!Schema::hasColumn('patients', 'current_medication')) {
                $table->text('current_medication')->nullable()->after('systemic_diseases_other');
            }

            // 是否怀孕 (仅女性)
            if (!Schema::hasColumn('patients', 'is_pregnant')) {
                $table->boolean('is_pregnant')->default(false)->after('current_medication');
            }

            // 是否哺乳期 (仅女性)
            if (!Schema::hasColumn('patients', 'is_breastfeeding')) {
                $table->boolean('is_breastfeeding')->default(false)->after('is_pregnant');
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
            $columns = ['drug_allergies', 'drug_allergies_other', 'systemic_diseases_other',
                        'current_medication', 'is_pregnant', 'is_breastfeeding'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('patients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
