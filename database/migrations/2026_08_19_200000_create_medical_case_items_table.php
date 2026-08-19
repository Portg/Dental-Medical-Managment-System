<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 病历分段明细：每一行是「牙位 + 文字」。
 *
 * 现状是每段一个 textarea + 整段共享一个牙位列表（examination_teeth = ["45","36"]），
 * 文字和牙位对不上号 —— 「45 缺失」和「36 龋坏」揉在一段话里，牙位只是一个并列的
 * 标签集合。于是「45 这颗牙历次做过什么」查不出来，按牙位的统计也做不了。
 *
 * 同类产品（轻松牙医）的病历里，检查/其他检查/诊断/治疗计划/治疗每一段都是可重复的
 * 「牙位 + 文字」行，每行独立增删。这张表就是那个结构。
 *
 * 与既有文本列的关系（重要）：
 *   行是唯一可编辑来源；medical_cases.examination / auxiliary_examination /
 *   diagnosis / treatment 以及 examination_teeth / related_teeth 全部改为**从行派生**，
 *   在保存时由 MedicalCaseService 重新生成。派生是单向的 —— 编辑器只读行、不读文本列。
 *
 *   保留文本列而不是删掉，是因为它们的消费方太多且都不该为此改动：病历打印
 *   （print.blade.php）、病历详情（show.blade.php）、API Resource、OCR 识别写入
 *   （OcrService）、工作日志（WorkLogService）。删列要同时改这五处，收益却是零 ——
 *   它们要的就是一段可读的文字。
 *
 * 存量病历：不做破坏性迁移。老病历没有行，加载时按「整段一行、tooth_no 为 null」
 * 合成显示，下次保存时落库。tooth_no 可空本身也是有意义的：不针对具体牙位的
 * 整体描述（「口腔卫生一般」）本来就没有牙位。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_case_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('medical_case_id');

            // examination / auxiliary_examination / diagnosis / treatment
            // 用字符串而不是 enum：加一段（比如单独的「治疗计划」）不必改表结构
            $table->string('section', 32);

            // 单个牙位（FDI，如 '45'、'55'）；null = 与具体牙位无关的整体描述
            $table->string('tooth_no', 20)->nullable();

            $table->text('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('cascade');

            // 取某份病历某一段的行，按顺序
            $table->index(['medical_case_id', 'section', 'sort_order'], 'mci_case_section_sort_idx');
            // 「这颗牙历次做过什么」—— 这张表存在的理由
            $table->index('tooth_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_case_items');
    }
};
