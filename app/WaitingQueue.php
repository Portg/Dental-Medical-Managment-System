<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class WaitingQueue extends Model
{
    use SoftDeletes;

    protected $table = 'waiting_queues';

    protected $fillable = [
        'branch_id',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'chair_id',
        'queue_number',
        'status',
        'check_in_time',
        'called_time',
        'treatment_start_time',
        'treatment_end_time',
        'estimated_wait_minutes',
        'visit_type',
        'notes',
        'called_by',
        'created_by'
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'called_time' => 'datetime',
        'treatment_start_time' => 'datetime',
        'treatment_end_time' => 'datetime',
    ];

    // 状态常量
    const STATUS_WAITING = 'waiting';
    const STATUS_CALLED = 'called';
    const STATUS_IN_TREATMENT = 'in_treatment';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    /**
     * 关联：患者
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * 关联：预约
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * 关联：医生
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * 关联：椅位
     */
    public function chair()
    {
        return $this->belongsTo(Chair::class, 'chair_id');
    }

    /**
     * 关联：门店
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * 关联：叫号人员
     */
    public function calledByUser()
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    /**
     * 获取已等待时间（分钟）
     */
    public function getWaitedMinutesAttribute()
    {
        if ($this->status === self::STATUS_WAITING) {
            return Carbon::parse($this->check_in_time)->diffInMinutes(now());
        } elseif ($this->called_time) {
            return Carbon::parse($this->check_in_time)->diffInMinutes($this->called_time);
        }
        return 0;
    }

    /**
     * 获取脱敏的患者姓名（如：张**）
     */
    public function getMaskedPatientNameAttribute()
    {
        $name = $this->patient->name ?? '';
        if (mb_strlen($name) <= 1) {
            return $name;
        }
        return mb_substr($name, 0, 1) . str_repeat('*', mb_strlen($name) - 1);
    }

    /**
     * 获取显示状态文本
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            self::STATUS_WAITING => __('waiting_queue.status.waiting'),
            self::STATUS_CALLED => __('waiting_queue.status.called'),
            self::STATUS_IN_TREATMENT => __('waiting_queue.status.in_treatment'),
            self::STATUS_COMPLETED => __('waiting_queue.status.completed'),
            self::STATUS_CANCELLED => __('waiting_queue.status.cancelled'),
            self::STATUS_NO_SHOW => __('waiting_queue.status.no_show'),
        ];
        return $statusMap[$this->status] ?? $this->status;
    }

    // ========== Scopes ==========

    /**
     * 筛选：等待中
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    /**
     * 筛选：已叫号
     */
    public function scopeCalled($query)
    {
        return $query->where('status', self::STATUS_CALLED);
    }

    /**
     * 筛选：就诊中
     */
    public function scopeInTreatment($query)
    {
        return $query->where('status', self::STATUS_IN_TREATMENT);
    }

    /**
     * 筛选：今日
     */
    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', today());
    }

    /**
     * 筛选：指定门店
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * 筛选：指定医生
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * 按排队号排序
     */
    public function scopeOrderByQueue($query)
    {
        return $query->orderBy('queue_number', 'asc');
    }

    // ========== 静态方法 ==========

    /**
     * 生成今日新的排队号码
     */
    public static function generateQueueNumber($branchId)
    {
        $maxNumber = self::forBranch($branchId)
            ->today()
            ->max('queue_number');

        return ($maxNumber ?? 0) + 1;
    }

    /**
     * 患者签到入队
     */
    public static function checkIn($appointmentId, $branchId, $createdBy = null)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        // 检查是否已签到
        $existing = self::where('appointment_id', $appointmentId)
            ->today()
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_NO_SHOW])
            ->first();

        if ($existing) {
            return $existing;
        }

        // 计算预计等待时间（根据当前排队人数和平均就诊时间）
        $waitingCount = self::forBranch($branchId)->waiting()->today()->count();
        $avgTreatmentMinutes = 30; // 默认平均就诊时间30分钟
        $estimatedWait = $waitingCount * $avgTreatmentMinutes;

        $queue = self::create([
            'branch_id' => $branchId,
            'appointment_id' => $appointmentId,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor,
            'queue_number' => self::generateQueueNumber($branchId),
            'status' => self::STATUS_WAITING,
            'check_in_time' => now(),
            'estimated_wait_minutes' => $estimatedWait,
            'visit_type' => $appointment->appointment_category ?? null,
            'created_by' => $createdBy
        ]);

        // 更新预约状态为已到院
        $appointment->update(['status' => 'checked_in']);

        return $queue;
    }

    /**
     * 叫号
     */
    public function callPatient($calledBy = null, $chairId = null)
    {
        $this->update([
            'status' => self::STATUS_CALLED,
            'called_time' => now(),
            'called_by' => $calledBy,
            'chair_id' => $chairId
        ]);

        return $this;
    }

    /**
     * 开始就诊
     */
    public function startTreatment()
    {
        $this->update([
            'status' => self::STATUS_IN_TREATMENT,
            'treatment_start_time' => now()
        ]);

        // 更新预约状态
        if ($this->appointment) {
            $this->appointment->update(['status' => 'in_progress']);
        }

        return $this;
    }

    /**
     * 完成就诊
     */
    public function completeTreatment()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'treatment_end_time' => now()
        ]);

        // 更新预约状态
        if ($this->appointment) {
            $this->appointment->update(['status' => 'completed']);
        }

        return $this;
    }

    /**
     * 取消
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason
        ]);

        return $this;
    }

    /**
     * 获取当前叫号信息（用于大屏显示）
     */
    public static function getCurrentCalling($branchId)
    {
        return self::forBranch($branchId)
            ->today()
            ->where('status', self::STATUS_CALLED)
            ->with(['patient', 'doctor', 'chair'])
            ->orderBy('called_time', 'desc')
            ->first();
    }

    /**
     * 获取候诊队列（用于大屏显示）
     */
    public static function getWaitingList($branchId, $limit = 10)
    {
        return self::forBranch($branchId)
            ->today()
            ->waiting()
            ->with(['patient', 'doctor'])
            ->orderByQueue()
            ->limit($limit)
            ->get();
    }

    /**
     * 获取当前就诊中的患者
     */
    public static function getInTreatmentList($branchId)
    {
        return self::forBranch($branchId)
            ->today()
            ->inTreatment()
            ->with(['patient', 'doctor', 'chair'])
            ->get();
    }
}
