<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 「按次核销」标记 —— 决定一个收费项目会不会进「剩余项目」。
 *
 * 为什么需要这个标记，而不是所有收费行都算余量：本系统的划价发生在**治疗
 * 之后**（医生做完这次的活，当场划价收钱），所以绝大多数收费行开出来的那
 * 一刻，服务就已经交付完了。若把每一行都算成「待执行」，剩余项目列表会被
 * 当天做完的补牙、拍片刷满，真正的预收款反而淹没在里面。
 *
 * 真正有余量的是**先收钱、后分次做**的那类：洁牙次卡、正畸全程、种植分期。
 * 这类项目在诊所心里是明确的一小撮，所以做成项目维护里的一个开关。
 *
 * 默认 false：对既有数据零影响，功能完全是诊所勾进来的。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_services', 'track_delivery')) {
            Schema::table('medical_services', function (Blueprint $table) {
                $table->boolean('track_delivery')->default(false)->after('is_favorite');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_services', 'track_delivery')) {
            Schema::table('medical_services', function (Blueprint $table) {
                $table->dropColumn('track_delivery');
            });
        }
    }
};
