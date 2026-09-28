<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 病历模板的学科分类树（最多三级）。
 *
 * 对齐视频：模板按学科分（口腔正畸学 / 颌面外科学 / 牙体牙髓病学 / 牙周病学 /
 * 颞颌关节病 / 口腔种植学 / 口腔修复学），并支持一级 / 二级 / 三级模板类型。
 *
 * **为什么不复用 medical_templates.category**：那一列已经有别的含义 —— 它是
 * 模板的**归属范围**（system / department / personal，决定谁能看见、谁能改），
 * 不是学科。把学科塞进去等于一列担两个语义，权限判断和分类筛选会互相踩。
 * 同理 type 是病历**段落**（主诉 / 诊断 / 治疗计划 / 病程记录），也不是学科。
 *
 * 所以新开一张表。列名用 template_category_id 而不是 category_id，是为了在
 * 代码里一眼能和既有的 category 区分开 —— 这两个词在本模块里指的是两回事。
 *
 * 层级不落成 level 字段：层级由 parent_id 链推出来，存一份冗余的深度，移动
 * 节点时就要连着子树一起改，漏一次树就歪了。三级上限在服务层校验。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('template_categories')) {
            Schema::create('template_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('_who_added')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['parent_id', 'sort_order']);
                $table->foreign('parent_id')->references('id')->on('template_categories');
            });
        }

        if (!Schema::hasColumn('medical_templates', 'template_category_id')) {
            Schema::table('medical_templates', function (Blueprint $table) {
                // 可空：既有模板一条都没归过类，硬性要求会让它们全部失效
                $table->unsignedBigInteger('template_category_id')->nullable()->after('category');
                $table->index('template_category_id');
                $table->foreign('template_category_id')->references('id')->on('template_categories');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_templates', 'template_category_id')) {
            Schema::table('medical_templates', function (Blueprint $table) {
                $table->dropForeign(['template_category_id']);
                $table->dropIndex(['template_category_id']);
                $table->dropColumn('template_category_id');
            });
        }

        Schema::dropIfExists('template_categories');
    }
};
