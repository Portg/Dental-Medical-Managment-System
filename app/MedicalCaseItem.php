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
        'medical_case_id', 'section', 'tooth_no', 'content', 'sort_order', '_who_added',
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
    public const SECTION_EXAMINATION = 'examination';
    public const SECTION_AUXILIARY   = 'auxiliary_examination';
    public const SECTION_DIAGNOSIS   = 'diagnosis';
    public const SECTION_TREATMENT   = 'treatment';

    public const SECTIONS = [
        self::SECTION_EXAMINATION,
        self::SECTION_AUXILIARY,
        self::SECTION_DIAGNOSIS,
        self::SECTION_TREATMENT,
    ];

    /**
     * 段落 → medical_cases 上对应的牙位列。
     *
     * 只有检查和诊断有牙位列（examination_teeth / related_teeth），
     * 其他检查与治疗没有 —— 保持现状，不为此加列。
     */
    public const TEETH_COLUMNS = [
        self::SECTION_EXAMINATION => 'examination_teeth',
        self::SECTION_DIAGNOSIS   => 'related_teeth',
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
