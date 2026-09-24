<?php

namespace App;

use App\Concerns\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 病历分段明细：一行 = 一个牙位 + 一段文字。
 *
 * 见 2026_08_19_200000 迁移的注释：行是唯一可编辑来源，medical_cases 上的
 * examination / diagnosis / treatment 等文本列由行派生。
 */
class MedicalCaseItem extends Model
{
    use SerializesDatesInAppTimezone, SoftDeletes;

    protected $fillable = [
        'medical_case_id', 'section', 'tooth_no', 'tooth_mark', 'content', 'sort_order', '_who_added',
    ];

    /**
     * 牙位标记（部位记录法里写在牙位号上下的符号）。
     *
     *     △ residual_root  残根 —— 牙冠基本没了，只剩牙根
     *     ✕ extracted      已拔除 / 该牙缺失
     *     — missing        缺失
     *
     * 存 slug 不存符号：符号是显示层的事。
     *
     * 「—」连起来画表示连冠固定假牙（跨牙关系），不在此列 —— 见
     * 2026_09_17_100000 迁移的说明，那是有意省略。
     */
    public const MARK_RESIDUAL_ROOT = 'residual_root';
    public const MARK_EXTRACTED     = 'extracted';
    public const MARK_MISSING       = 'missing';

    public const MARKS = [
        self::MARK_RESIDUAL_ROOT,
        self::MARK_EXTRACTED,
        self::MARK_MISSING,
    ];

    /** slug → 部位记录法的符号 */
    public const MARK_SYMBOLS = [
        self::MARK_RESIDUAL_ROOT => '△',
        self::MARK_EXTRACTED     => '✕',
        self::MARK_MISSING       => '—',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 分行录入的段落。
     *
     * 主诉 / 现病史 / 医嘱不在其中：那些是就诊层面的叙述，本来就不按牙位分行
     * （同类产品也是把它们留作整段文本）。
     */
    public const SECTION_EXAMINATION    = 'examination';
    public const SECTION_AUXILIARY      = 'auxiliary_examination';
    public const SECTION_DIAGNOSIS      = 'diagnosis';
    /**
     * 治疗计划：打算做什么，跨次不变。
     *
     * 与 SECTION_TREATMENT（这次实际做了什么）分开 —— 活动义齿要来好几次，
     * 计划一直是「活动义齿修复」，而处置这次是「制取印模」下次是「试戴」。
     * 参考产品的病历也是这么拆的。
     *
     * 与 treatment_plans 表无关：那张表是报价单 + 风险告知 + 电子签名。
     */
    public const SECTION_TREATMENT_PLAN = 'treatment_plan';
    public const SECTION_TREATMENT      = 'treatment';

    /**
     * 本表承接的段落。
     *
     * **诊断不在其中** —— 诊断走 diagnoses 表：那张表本来就是一行一条诊断，
     * 还带 ICD 编码、严重程度、转归状态，是本表存不了的东西（ICD 是医保与
     * 病案质控要的）。同一条诊断不在两张表里各存一份。
     * 见 2026_08_25_100000 迁移的说明。
     */
    public const SECTIONS = [
        self::SECTION_EXAMINATION,
        self::SECTION_AUXILIARY,
        self::SECTION_TREATMENT_PLAN,
        self::SECTION_TREATMENT,
    ];

    /** 诊断段的 key，值仍是 'diagnosis'，但落在 diagnoses 表 */
    public const DIAGNOSIS_SECTION = self::SECTION_DIAGNOSIS;

    /**
     * 段落 → medical_cases 上对应的牙位列。
     *
     * 只有检查和诊断有牙位列（examination_teeth / related_teeth），
     * 其他检查与治疗没有 —— 保持现状，不为此加列。
     */
    /**
     * 象限码：一位数字的 tooth_no，表示「这个区，但不指定是哪颗牙」。
     *
     * 部位记录法里「符号直接取代数字」是正经写法 —— 医生在左上格里只画一个 △，
     * 意思是「患者右上区有颗牙是残根」，牙位号不写。两位 FDI 全码表达不了这件事
     * （它必须指到具体某颗牙），所以借一位数字表示象限：
     *
     *     1-4  恒牙的四个象限，与 FDI 首位一致
     *     5-8  乳牙的四个象限
     *
     * 与 FDI 全码共用 tooth_no 一列是有意的：象限本来就是 FDI 首位的含义，
     * toothQuadrant() 一直只读首位，两者落在同一套定位规则里。代价是这一列
     * 有两种粒度，所以凡是「按牙位查」「往牙位图投影」的地方都要先把它排掉 ——
     * 见 isQuadrantCode() 的调用点。
     */
    public static function isQuadrantCode(?string $toothNo): bool
    {
        return $toothNo !== null && preg_match('/^[1-8]$/', $toothNo) === 1;
    }

    public const TEETH_COLUMNS = [
        self::SECTION_EXAMINATION => 'examination_teeth',
        // related_teeth 由 diagnoses 表的牙位派生，不在本表的段落里
    ];

    public function medicalCase()
    {
        return $this->belongsTo(MedicalCase::class, 'medical_case_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * 「这颗牙历次做过什么」—— 这张表存在的理由。
     * 按病历日期倒序，最近的在前。
     */
    public function scopeForTooth($query, string $toothNo)
    {
        return $query->where('tooth_no', $toothNo);
    }
}
