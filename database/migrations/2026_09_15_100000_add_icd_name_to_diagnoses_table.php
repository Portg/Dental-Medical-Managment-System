<?php

use App\Services\MedicalCaseService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 诊断的 ICD 名称随病历落库。
 *
 * 原来 diagnoses 只存 icd_code。医生在下拉里选的是「K04.0 - 牙髓炎」，
 * 落库只落了 K04.0，名称当场丢掉 —— 重新打开病历，下拉框里只剩一个裸编码，
 * 医生没法确认自己当初选对没有；打印出来也是一个裸编码缀在诊断后面。
 *
 * 为什么存下来而不是渲染时按码表查：病历是医疗文书，写下时是什么就该永远是什么。
 * 码表是硬编码在 MedicalCaseService::ICD10_CODES 里的 29 条，名称走
 * odontogram.* 的翻译 —— 改一次翻译、动一次列表，所有历史病历的诊断含义就跟着变，
 * 这在医疗文书上是不能接受的。多存一列换来的是记录不可变。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('diagnoses', 'icd_name')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->string('icd_name', 255)->nullable()->after('icd_code');
            });
        }

        // 回填存量：现有记录的名称还能按码表反查回来。趁码表还没动过补上，
        // 再晚就只能留着裸编码了。
        $service = app(MedicalCaseService::class);

        DB::table('diagnoses')
            ->whereNotNull('icd_code')
            ->where('icd_code', '<>', '')
            ->whereNull('icd_name')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($service) {
                foreach ($rows as $row) {
                    $name = $service->icd10Name($row->icd_code);

                    if ($name === null) {
                        continue;   // 码表里没有的编码，宁可留空也不编一个名字
                    }

                    DB::table('diagnoses')->where('id', $row->id)->update(['icd_name' => $name]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('diagnoses', 'icd_name')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn('icd_name');
            });
        }
    }
};
