<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Thomasjohnkane\Snooze\Traits\SnoozeNotifiable;

class Patient extends Model
{
    use Notifiable, SoftDeletes, SnoozeNotifiable;

    protected $fillable = [
        'patient_no', 'patient_code', 'status', 'merged_to_id',
        'surname', 'othername', 'gender', 'dob', 'date_of_birth', 'age',
        'ethnicity', 'marital_status', 'education', 'blood_type',
        'email', 'phone_no', 'alternative_no', 'address',
        'medication_history', 'drug_allergies', 'drug_allergies_other',
        'systemic_diseases', 'systemic_diseases_other', 'current_medication',
        'is_pregnant', 'is_breastfeeding',
        'tags', 'notes', 'nin', 'photo', 'profession',
        'next_of_kin', 'next_of_kin_no', 'next_of_kin_address',
        'has_insurance', 'insurance_company_id', 'source_id', '_who_added',
        'member_no', 'member_level_id', 'member_balance', 'member_points',
        'total_consumption', 'member_since', 'member_expiry', 'member_status'
    ];

    /**
     * 民族选项（中国56个民族）
     */
    public static $ethnicityOptions = [
        'han' => '汉族',
        'zhuang' => '壮族',
        'hui' => '回族',
        'manchu' => '满族',
        'uyghur' => '维吾尔族',
        'miao' => '苗族',
        'yi' => '彝族',
        'tujia' => '土家族',
        'tibetan' => '藏族',
        'mongol' => '蒙古族',
        'dong' => '侗族',
        'bouyei' => '布依族',
        'yao' => '瑶族',
        'bai' => '白族',
        'korean' => '朝鲜族',
        'hani' => '哈尼族',
        'li' => '黎族',
        'kazak' => '哈萨克族',
        'dai' => '傣族',
        'she' => '畲族',
        'other' => '其他',
    ];

    /**
     * 婚姻状况选项
     */
    public static $maritalStatusOptions = [
        'single' => '未婚',
        'married' => '已婚',
        'divorced' => '离异',
        'widowed' => '丧偶',
        'other' => '其他',
    ];

    /**
     * 教育程度选项
     */
    public static $educationOptions = [
        'primary' => '小学',
        'junior_high' => '初中',
        'senior_high' => '高中/中专',
        'college' => '大专',
        'bachelor' => '本科',
        'master' => '硕士',
        'doctor' => '博士',
        'other' => '其他',
    ];

    /**
     * 血型选项
     */
    public static $bloodTypeOptions = [
        'A' => 'A型',
        'B' => 'B型',
        'AB' => 'AB型',
        'O' => 'O型',
        'A_Rh_negative' => 'A型Rh阴性',
        'B_Rh_negative' => 'B型Rh阴性',
        'AB_Rh_negative' => 'AB型Rh阴性',
        'O_Rh_negative' => 'O型Rh阴性',
        'unknown' => '未知',
    ];

    protected $casts = [
        'drug_allergies' => 'array',
        'systemic_diseases' => 'array',
        'tags' => 'array',
        'date_of_birth' => 'date',
        'member_since' => 'date',
        'member_expiry' => 'date',
        'member_balance' => 'decimal:2',
        'total_consumption' => 'decimal:2',
        'is_pregnant' => 'boolean',
        'is_breastfeeding' => 'boolean',
        'has_insurance' => 'boolean',
    ];

    /**
     * 预设的药物过敏选项
     */
    public static $allergyOptions = [
        'penicillin' => '青霉素',
        'cephalosporin' => '头孢',
        'sulfa' => '磺胺',
        'anesthetic' => '麻醉药',
        'iodine' => '碘',
        'latex' => '乳胶',
    ];

    /**
     * 预设的全身病史选项
     */
    public static $diseaseOptions = [
        'hypertension' => '高血压',
        'diabetes' => '糖尿病',
        'heart_disease' => '心脏病',
        'hepatitis' => '肝炎',
        'infectious_disease' => '传染病',
        'blood_disease' => '血液病',
    ];

    /**
     * 检查患者是否有任何过敏
     */
    public function hasAllergies()
    {
        return !empty($this->drug_allergies) || !empty($this->drug_allergies_other);
    }

    /**
     * 获取格式化的过敏信息
     */
    public function getAllergiesDisplayAttribute()
    {
        $allergies = [];
        if (!empty($this->drug_allergies)) {
            foreach ($this->drug_allergies as $allergy) {
                if (isset(self::$allergyOptions[$allergy])) {
                    $allergies[] = self::$allergyOptions[$allergy];
                }
            }
        }
        if (!empty($this->drug_allergies_other)) {
            $allergies[] = $this->drug_allergies_other;
        }
        return implode('、', $allergies);
    }

    /**
     * 获取格式化的全身病史信息
     */
    public function getDiseasesDisplayAttribute()
    {
        $diseases = [];
        if (!empty($this->systemic_diseases)) {
            foreach ($this->systemic_diseases as $disease) {
                if (isset(self::$diseaseOptions[$disease])) {
                    $diseases[] = self::$diseaseOptions[$disease];
                }
            }
        }
        if (!empty($this->systemic_diseases_other)) {
            $diseases[] = $this->systemic_diseases_other;
        }
        return implode('、', $diseases);
    }

    /**
     * Accessor: join name based on locale
     */
    public function getFullNameAttribute()
    {
        if (app()->getLocale() === 'zh-CN') {
            return $this->surname . $this->othername;
        }
        return $this->surname . ' ' . $this->othername;
    }

    public function routeNotificationForSms($notifiable)
    {
        return 'identifier-from-notification-for-sms: ' . $this->id;
    }

    public function InsuranceCompany()
    {
        return $this->belongsTo('App\InsuranceCompany', 'insurance_company_id');
    }

    public function patientTags()
    {
        return $this->belongsToMany('App\PatientTag', 'patient_tag_pivot', 'patient_id', 'tag_id');
    }

    public function source()
    {
        return $this->belongsTo('App\PatientSource', 'source_id');
    }

    public function medicalCases()
    {
        return $this->hasMany('App\MedicalCase', 'patient_id');
    }

    public function diagnoses()
    {
        return $this->hasMany('App\Diagnosis', 'patient_id');
    }

    public function progressNotes()
    {
        return $this->hasMany('App\ProgressNote', 'patient_id');
    }

    public function treatmentPlans()
    {
        return $this->hasMany('App\TreatmentPlan', 'patient_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany('App\VitalSign', 'patient_id');
    }

    public function images()
    {
        return $this->hasMany('App\PatientImage', 'patient_id');
    }

    public function followups()
    {
        return $this->hasMany('App\PatientFollowup', 'patient_id');
    }

    public function memberLevel()
    {
        return $this->belongsTo('App\MemberLevel', 'member_level_id');
    }

    public function memberTransactions()
    {
        return $this->hasMany('App\MemberTransaction', 'patient_id');
    }

    public function appointments()
    {
        return $this->hasMany('App\Appointment', 'patient_id');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'patient_id');
    }

    public function dentalCharts()
    {
        return $this->hasMany('App\DentalChart', 'patient_id');
    }

    public function prescriptions()
    {
        return $this->hasMany('App\Prescription', 'patient_id');
    }

    public function analytics()
    {
        return $this->hasOne('App\PatientAnalytics', 'patient_id');
    }

    /**
     * 合并到的患者
     */
    public function mergedTo()
    {
        return $this->belongsTo('App\Patient', 'merged_to_id');
    }

    /**
     * 被合并的患者
     */
    public function mergedPatients()
    {
        return $this->hasMany('App\Patient', 'merged_to_id');
    }

    /**
     * Check if patient is a member.
     */
    public function getIsMemberAttribute()
    {
        return $this->member_status === 'Active';
    }

    /**
     * Check if patient is active
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    /**
     * Scope for active patients
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for merged patients
     */
    public function scopeMerged($query)
    {
        return $query->where('status', 'merged');
    }

    /**
     * Generate unique patient code
     */
    public static function generatePatientCode($branchCode = 'HQ')
    {
        $prefix = $branchCode . date('Ymd');
        $lastPatient = self::where('patient_code', 'like', $prefix . '%')
            ->orderBy('patient_code', 'desc')
            ->first();

        if ($lastPatient && $lastPatient->patient_code) {
            $lastNumber = intval(substr($lastPatient->patient_code, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique member number
     */
    public static function generateMemberNo()
    {
        $year = date('Y');
        $lastMember = self::whereNotNull('member_no')
            ->whereYear('member_since', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMember && $lastMember->member_no) {
            $lastNumber = intval(substr($lastMember->member_no, -5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'M' . $year . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public static function PatientNumber()
    {
        $latest = self::latest()->first();
        if (!$latest) {
            return date('Y') . "" . '0001';
        } else if ($latest->deleted_at != "null") {
            return time() + $latest->id + 1;
        } else {
            return date('Y') . "" . sprintf('%04d', $latest->id + 1);
        }
    }
}
