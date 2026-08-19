<?php

use App\Http\Helper\NameHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 患者姓名首拼，用于检索：刘万友 → lwy。
 *
 * 前台接电话时打 lwy 比打中文快得多，是中文诊所软件的标配检索方式
 * （同类产品的搜索框提示词就是「姓名/首拼/手机号/病历号」）。
 *
 * 落库存字段而不是查询时算：查询时算既走不了索引，也没法做前缀匹配。
 * 写入由 Patient 模型的 saving 钩子负责，覆盖所有写入路径
 * （建档、API、在线预约、Excel 导入、OCR）。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('patients', 'name_py')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('name_py', 64)->nullable()->after('othername');
                $table->index('name_py');
            });
        }

        // 回填存量患者。分批处理：老库患者可能上万，一次性 get() 会把内存打满。
        DB::table('patients')
            ->select('id', 'surname', 'othername')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $py = NameHelper::abbr(trim(($row->surname ?? '') . ($row->othername ?? '')));

                    if ($py === '') {
                        continue;
                    }

                    DB::table('patients')->where('id', $row->id)->update(['name_py' => $py]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('patients', 'name_py')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropIndex(['name_py']);
                $table->dropColumn('name_py');
            });
        }
    }
};
