<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Permission;
use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemsSeeder extends Seeder
{
    /**
     * 角色缩写：S=super-admin, A=admin, D=doctor, N=nurse, R=receptionist, I=inventory-manager
     */
    private array $roleIds = [];
    private array $permIds = [];

    /** 本次运行中由 seeder 定义（因而由其托管）的菜单项 ID */
    private array $managedIds = [];

    private int $created = 0;
    private int $updated = 0;

    /**
     * 幂等执行：按 title_key 做 upsert，不再 truncate。
     *
     * 此前本 seeder 会 truncate menu_items / role_menu_items 再全量重建，
     * 造成两类破坏：
     *   1. 由迁移添加、而 seeder 中没有的菜单项被永久抹掉（库存查询、申领管理、
     *      盘点管理、批量导入即因此消失，功能健在但侧边栏无入口）；
     *   2. truncate 重置自增 ID，令 roles.hidden_menu_items 中存的菜单 ID
     *      全部变成悬空引用，且静默失效。
     *
     * 改为 upsert 后：ID 稳定，未知菜单项不再被删除（仅报告，交由人工判断），
     * 且 is_active 不覆盖既有值 —— 尊重管理员在菜单管理界面所做的启停。
     */
    public function run(): void
    {
        $this->roleIds = Role::pluck('id', 'slug')->toArray();
        $this->permIds = Permission::pluck('id', 'slug')->toArray();

        $this->guardPrerequisites();

        $this->seedMenuTree();

        $this->reportUnmanaged();

        $this->command?->info(
            "菜单同步完成：新增 {$this->created} 项，更新 {$this->updated} 项。"
        );

        // 清除菜单缓存，确保侧边栏立即生效
        \Illuminate\Support\Facades\Cache::forget('menu_tree:all');
    }

    /**
     * 前置校验：任一条不满足都会产出一棵错误的菜单树，宁可中止也不要写坏数据。
     */
    private function guardPrerequisites(): void
    {
        // permission_id 为 null 在 MenuService 中意味着「全员可见」。若权限表为空
        // （例如先于 PermissionsTableSeeder 执行），整棵菜单树会对所有角色开放。
        if (empty($this->permIds)) {
            throw new \RuntimeException(
                'permissions 表为空，请先执行 PermissionsTableSeeder。'
                . '否则菜单权限会被写成 null，等同于对全员可见。'
            );
        }

        if (empty($this->roleIds)) {
            throw new \RuntimeException('roles 表为空，请先执行 RolesTableSeeder。');
        }

        // upsert 以 title_key 为匹配键，重复会导致只更新其中一条而另一条静默漂移。
        // 目前表上没有 title_key 唯一索引，故在此显式校验。
        $dupes = DB::table('menu_items')
            ->select('title_key')
            ->groupBy('title_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('title_key');

        if ($dupes->isNotEmpty()) {
            throw new \RuntimeException(
                'menu_items 中存在重复的 title_key，upsert 无法安全匹配：'
                . $dupes->implode(', ') . '。请先清理重复项。'
            );
        }
    }

    /**
     * 报告库中存在、但本 seeder 未定义的菜单项。
     *
     * 有意只报告不删除：这些项通常来自迁移，删除它们正是此前 truncate 造成的问题。
     * 是否清理由人工判断。
     */
    private function reportUnmanaged(): void
    {
        $unmanaged = MenuItem::whereNotIn('id', $this->managedIds ?: [0])
            ->orderBy('id')
            ->get(['id', 'title_key', 'url']);

        if ($unmanaged->isEmpty()) {
            return;
        }

        $this->command?->warn(
            "以下 {$unmanaged->count()} 项菜单存在于数据库但未在 MenuItemsSeeder 中定义，"
            . '已原样保留（未删除）。若为迁移添加的常驻菜单，建议补入本 seeder：'
        );
        foreach ($unmanaged as $item) {
            $this->command?->warn("  [{$item->id}] {$item->title_key} → " . ($item->url ?: '(目录节点)'));
        }
    }

    private function seedMenuTree(): void
    {
        // ── Level 1: Top-level sections ────────────────────────────────

        // 权限须与 TodayWorkController 的 can:view-appointments 一致，否则库管落地页 403
        $todayWork = $this->item(null, 'menu.today_work', 'today-work', 'icon-energy', 'view-appointments', 10, 'SADNR');

        $patientCenter = $this->item(null, 'menu.patient_center', null, 'icon-users', null, 20, 'SADNR');
        $this->seedPatientCenter($patientCenter);

        $clinicalCenter = $this->item(null, 'menu.clinical_center', null, 'icon-briefcase', null, 30, 'SADNR');
        $this->seedClinicalCenter($clinicalCenter);

        $clinicAffairs = $this->item(null, 'menu.clinic_affairs', null, 'icon-layers', null, 35, 'SADN');
        $this->seedClinicAffairs($clinicAffairs);

        $opsCenter = $this->item(null, 'menu.operations_center', null, 'icon-wallet', null, 40, 'SADNR');
        $this->seedOperationsCenter($opsCenter);

        $dataCenter = $this->item(null, 'menu.data_center', null, 'icon-graph', null, 50, 'SAR');
        $this->seedDataCenter($dataCenter);

        $sysSettings = $this->item(null, 'menu.system_settings', null, 'icon-settings', null, 60, 'SA');
        $this->seedSystemSettings($sysSettings);
    }

    // ── 2. Patient Center ──────────────────────────────────────────────

    private function seedPatientCenter(int $parentId): void
    {
        // 2.1 Patient Files — always a group (directory), all roles see patients_list
        $pfGroup = $this->item($parentId, 'menu.group_patient_management', null, 'icon-list', 'view-patients', 10, 'SADNR');
        $this->item($pfGroup, 'menu.patients_list', 'patients', null, 'view-patients', 10, 'SADNR');
        $this->item($pfGroup, 'menu.ocr_recognize', 'ocr-recognize', null, 'create-patients', 15, 'SAR');
        // originally added by 2026_05_17_000002 / 000004 migrations
        $this->item($pfGroup, 'menu.work_log_recognize', 'work-log-ocr', null, 'create-patients', 16, 'SAR');
        $this->item($pfGroup, 'menu.work_log', 'work-logs', null, 'create-patients', 17, 'SAR');
        $this->item($pfGroup, 'menu.patient_tags', 'patient-tags', null, 'manage-patient-settings', 20, 'SA');
        $this->item($pfGroup, 'menu.patient_sources', 'patient-sources', null, 'manage-patient-settings', 30, 'SA');

        // 2.2 Membership — SAR sees group with children, DN sees direct link
        $memGroup = $this->item($parentId, 'menu.group_member_management', 'members', 'icon-badge', 'manage-members', 20, 'SADNR');
        $this->item($memGroup, 'menu.members_list', 'members', null, 'manage-members', 10, 'SAR');
        $this->item($memGroup, 'menu.member_levels', 'member-levels', null, 'manage-members', 20, 'SA');
        $this->item($memGroup, 'menu.coupons', 'coupons', null, 'manage-members', 30, 'SAR');

        // 2.3 Patient Care — GROUP for SANR (nurse has fewer children)
        $careGroup = $this->item($parentId, 'menu.group_patient_care', null, 'icon-call-out', null, 30, 'SANR');
        $this->item($careGroup, 'menu.patient_followups', 'patient-followups', null, 'view-patients', 10, 'SANR');
        $this->item($careGroup, 'menu.birthday_wishes', 'birthday-wishes', null, 'view-patients', 20, 'SANR');
        $this->item($careGroup, 'menu.satisfaction_survey', 'satisfaction-surveys', null, 'view-surveys', 30, 'SAR');

        // 2.4 Image Data — DIRECT for all
        $this->item($parentId, 'menu.group_image_data', 'patient-images', 'icon-picture', 'view-patients', 40, 'SADNR');
    }

    // ── 3. Clinical Center ─────────────────────────────────────────────

    private function seedClinicalCenter(int $parentId): void
    {
        // 3.1 Appointment Management — GROUP for all, different children per role
        $apptGroup = $this->item($parentId, 'menu.group_appointment_management', null, 'icon-calendar', null, 10, 'SADNR');
        $this->item($apptGroup, 'menu.appointments', 'appointments', null, 'view-appointments', 10, 'SADNR');
        $this->item($apptGroup, 'menu.doctor_schedules', 'doctor-schedules', null, 'manage-schedules', 20, 'SAR');
        $this->item($apptGroup, 'menu.online_bookings', 'online-bookings', null, 'view-appointments', 30, 'SAR');
        $this->item($apptGroup, 'menu.waiting_queue', 'waiting-queue', null, 'view-appointments', 40, 'SANR');
        $this->item($apptGroup, 'menu.doctor_queue', 'doctor-queue', null, 'view-appointments', 50, 'D');

        // 3.2 Medical Records — SAD sees group with children, N sees direct link
        $mrGroup = $this->item($parentId, 'menu.group_medical_records', 'medical-cases', 'icon-doc', 'manage-medical-cases', 20, 'SADN');
        $this->item($mrGroup, 'menu.medical_cases', 'medical-cases', null, 'manage-medical-cases', 10, 'SAD');
        $this->item($mrGroup, 'menu.dental_charting', 'dental-charting', null, 'manage-medical-cases', 20, 'SAD');
        $this->item($mrGroup, 'menu.prescriptions', 'prescriptions', null, 'manage-treatments', 30, 'SAD');

        // 3.3 Treatment Plans — DIRECT for SAD
        $this->item($parentId, 'menu.group_treatment_plan', 'treatment-plans', 'icon-list', 'manage-treatments', 30, 'SAD');

        // 3.4 Clinical Config — GROUP for SAD (Doctor has fewer children)
        $ccGroup = $this->item($parentId, 'menu.group_clinical_config', null, 'icon-wrench', null, 40, 'SAD');
        $this->item($ccGroup, 'menu.service_items', 'clinic-services', null, 'manage-medical-services', 10, 'SA');
        $this->item($ccGroup, 'menu.medical_templates', 'medical-templates', null, 'manage-medical-services', 20, 'SAD');
        $this->item($ccGroup, 'menu.quick_phrases', 'quick-phrases', null, 'manage-medical-services', 30, 'SAD');

        // 3.5 我的绩效 — 医生查看本人绩效的专属入口（originally added by 2026_03_16_100023）
        // DoctorReportController 按 is_doctor 将数据限定为本人；管理员看全院绩效走
        // 运营中心 › 绩效管理，两者以权限区分，不互相覆盖。
        $this->item($parentId, 'menu.my_performance', 'doctor-report', 'icon-graph', 'view-own-doctor-report', 90, 'SD');
    }

    // ── 4. Operations Center ───────────────────────────────────────────

    private function seedOperationsCenter(int $parentId): void
    {
        // 4.1 Billing — GROUP for all 5 roles, same 4 children
        $billGroup = $this->item($parentId, 'menu.group_billing', null, 'icon-doc', null, 10, 'SADNR');
        $this->item($billGroup, 'menu.invoices', 'invoices', null, 'view-invoices', 10, 'SADNR');
        $this->item($billGroup, 'menu.quotations', 'quotations', null, 'manage-quotations', 20, 'SADNR');
        $this->item($billGroup, 'menu.refunds', 'refunds', null, 'manage-refunds', 30, 'SADNR');
        // 审批折扣属于改单据，须与 InvoiceController 的 can:edit-invoices 一致
        $this->item($billGroup, 'menu.pending_discount_approvals', 'invoices/pending-discount-approvals', null, 'edit-invoices', 40, 'SADNR');

        // 4.2 Insurance Claims — GROUP for S, DIRECT for A and D
        // S sees group with children, A sees direct link to insurance-companies, D sees doctor-claims (via url_override)
        $insGroup = $this->item($parentId, 'menu.group_insurance_claims', 'insurance-companies', 'icon-shield', 'manage-insurance', 20, 'SAD', ['D' => 'doctor-claims']);
        $this->item($insGroup, 'menu.insurance_companies', 'insurance-companies', null, 'manage-insurance', 10, 'S');
        $this->item($insGroup, 'menu.claim_rates', 'claim-rates', null, 'manage-insurance', 20, 'S');
        $this->item($insGroup, 'menu.doctor_claims', 'doctor-claims', null, 'manage-doctor-claims', 30, 'S');

        // 4.3 Accounts — SA sees group with children, R sees direct link
        $accGroup = $this->item($parentId, 'menu.group_accounts_management', 'self-accounts', 'icon-wallet', 'manage-accounting', 30, 'SAR');
        $this->item($accGroup, 'menu.self_accounts', 'self-accounts', null, 'manage-accounting', 10, 'SA');
        $this->item($accGroup, 'menu.charts_of_accounts', 'charts-of-accounts', null, 'manage-accounting', 20, 'SA');
        $this->item($accGroup, 'menu.sms_credit', 'sms-transactions', null, 'manage-sms', 30, 'SA');

        // 4.4 Consumables — GROUP for SAN (Nurse has fewer items)
        $conGroup = $this->item($parentId, 'menu.group_consumables', null, 'icon-layers', null, 40, 'SANI');
        // originally added by 2026_03_16_100008 migration（父级经 100015 修正为 group_consumables）
        $this->item($conGroup, 'inventory.inventory_query', 'inventory-query', null, 'manage-inventory', 8, 'SAI');
        $this->item($conGroup, 'inventory.stock_in', 'stock-ins', null, 'manage-inventory', 10, 'SAN');
        $this->item($conGroup, 'inventory.stock_out', 'stock-outs', null, 'manage-inventory', 20, 'SAN');
        // 须与 ServiceConsumableController 的 can:manage-medical-services 一致
        $this->item($conGroup, 'inventory.service_consumables', 'service-consumables', null, 'manage-medical-services', 30, 'SAN');
        $this->item($conGroup, 'inventory.categories', 'inventory-categories', null, 'manage-inventory', 40, 'SA');
        $this->item($conGroup, 'inventory.items', 'inventory-items', null, 'manage-inventory', 50, 'SA');
        // originally added by 2026_03_16_100011 / 100013 / 100014 migrations（父级经 100015 修正）
        // 申领管理需 request-inventory（医生/护士/库管均持有），审批环节在 Controller 内另按 manage-inventory 区分
        $this->item($conGroup, 'menu.requisition_management', 'requisitions', 'fa fa-file-text-o', 'request-inventory', 90, 'SADNI');
        $this->item($conGroup, 'menu.inventory_check_management', 'inventory-checks', 'fa fa-check-square-o', 'manage-inventory', 95, 'SAI');
        $this->item($conGroup, 'inventory.bulk_import', 'inventory-import', 'fa fa-file-excel-o', 'manage-inventory', 98, 'SAI');

        // 4.5 Suppliers — DIRECT for SA
        $this->item($parentId, 'menu.group_supplier', 'suppliers', 'icon-handbag', 'manage-inventory', 50, 'SA');

        // 4.6 Lab Cases — GROUP for S only
        $labGroup = $this->item($parentId, 'menu.group_lab_management', null, 'icon-wrench', null, 60, 'S');
        $this->item($labGroup, 'menu.lab_cases', 'lab-cases', null, 'manage-labs', 10, 'S');
        $this->item($labGroup, 'menu.labs', 'labs', null, 'manage-labs', 20, 'S');

        // 4.7 Employees — GROUP for SA
        $empGroup = $this->item($parentId, 'menu.group_employee', null, 'icon-briefcase', null, 70, 'SA');
        $this->item($empGroup, 'menu.employee_contracts', 'employee-contracts', null, 'manage-employees', 10, 'SA');
        $this->item($empGroup, 'menu.employee_payslips', 'payslips', null, 'manage-payroll', 20, 'SA');
        $this->item($empGroup, 'menu.salary_payment', 'salary-advances', null, 'manage-payroll', 30, 'SA');

        // Individual Payslip — DIRECT for DNR
        $this->item($parentId, 'menu.individual_payslip', 'individual-payslips', 'icon-briefcase', null, 71, 'DNR');

        // 4.8 Performance — SA sees group with children, D sees direct link
        // 全院绩效，管理员视角。医生查看本人绩效走「诊疗中心 › 我的绩效」，
        // 两个入口以权限区分，不要把这里改成 view-own-doctor-report 而造成重复。
        $perfGroup = $this->item($parentId, 'menu.group_performance', 'doctor-performance-report', 'icon-calculator', 'view-reports', 80, 'SA');
        $this->item($perfGroup, 'menu.commission_rules', 'commission-rules', null, 'manage-doctor-claims', 10, 'SA');
        $this->item($perfGroup, 'menu.doctor_performance_report', 'doctor-performance-report', null, 'view-reports', 20, 'SA');

        // 4.9 Attendance & Leave — GROUP for SA, DIRECT leave-requests for DNR
        $leaveGroup = $this->item($parentId, 'menu.group_attendance_leave', null, 'icon-calendar', null, 90, 'SA');
        $this->item($leaveGroup, 'menu.holidays', 'holidays', null, 'manage-holidays', 10, 'SA');
        $this->item($leaveGroup, 'menu.leave_types', 'leave-types', null, 'manage-leave', 20, 'SA');
        $this->item($leaveGroup, 'menu.leave_requests', 'leave-requests', null, null, 30, 'SADNR');
        $this->item($leaveGroup, 'menu.leave_approval', 'leave-requests-approval', null, 'manage-leave', 40, 'SA');
    }

    // ── 5. Data Center ─────────────────────────────────────────────────

    private function seedDataCenter(int $parentId): void
    {
        // 5.0 Business Cockpit — 经营驾驶舱
        $this->item($parentId, 'menu.business_cockpit', 'business-cockpit', 'icon-speedometer', 'view-reports', 5, 'SA');

        // 5.1 Revenue Analysis — GROUP for SA
        $revGroup = $this->item($parentId, 'menu.group_revenue_analysis', null, 'icon-bar-chart', null, 10, 'SA');
        // originally added by 2026_03_16_100003 migration
        $this->item($revGroup, 'menu.financial_calendar', 'financial-calendar', null, 'view-reports', 5, 'SA');
        $this->item($revGroup, 'menu.general_income_report', 'invoice-payments-report', null, 'view-reports', 10, 'SA');
        $this->item($revGroup, 'menu.procedures_income_report', 'procedure-income-report', null, 'view-reports', 20, 'SA');
        $this->item($revGroup, 'menu.cash_summary_report', 'cash-summary-report', null, 'view-reports', 25, 'SA');
        $this->item($revGroup, 'menu.aged_receivable_report', 'debtors', null, 'view-reports', 30, 'SA');
        $this->item($revGroup, 'menu.financial_detail_report', 'financial-detail-report', null, 'view-reports', 35, 'SA');
        // originally added by 2026_03_16_100007 migration
        $this->item($revGroup, 'menu.unpaid_invoices_report', 'unpaid-invoices', null, 'view-reports', 40, 'SA');

        // 5.2 Business Analysis — GROUP for SA
        $bizGroup = $this->item($parentId, 'menu.group_business_analysis', null, 'icon-pie-chart', null, 20, 'SA');
        $this->item($bizGroup, 'menu.revisit_rate_report', 'revisit-rate-report', null, 'view-reports', 10, 'SA');
        $this->item($bizGroup, 'menu.patient_source_report', 'patient-source-report', null, 'view-reports', 20, 'SA');
        $this->item($bizGroup, 'menu.appointment_analytics_report', 'appointment-analytics-report', null, 'view-reports', 30, 'SA');
        // originally added by 2026_03_16_100003 migration
        $this->item($bizGroup, 'menu.lab_statistics_report', 'lab-statistics-report', null, 'view-reports', 45, 'SA');
        $this->item($bizGroup, 'menu.treatment_plan_completion_report', 'treatment-plan-completion-report', null, 'view-reports', 50, 'SA');
        $this->item($bizGroup, 'menu.monthly_business_summary_report', 'monthly-business-summary-report', null, 'view-reports', 60, 'SA');
        $this->item($bizGroup, 'menu.patient_demographics_report', 'patient-demographics-report', null, 'view-reports', 70, 'SA');
        $this->item($bizGroup, 'menu.doctor_workload_report', 'doctor-workload-report', null, 'view-reports', 80, 'SA');
        $this->item($bizGroup, 'menu.quotation_conversion_report', 'quotation-conversion-report', null, 'view-reports', 90, 'SA');

        // 5.3 Expense Analysis — GROUP for SAR
        $expGroup = $this->item($parentId, 'menu.group_expense_analysis', null, 'icon-basket', null, 30, 'SAR');
        $this->item($expGroup, 'menu.expense_items', 'expense-categories', null, 'manage-expenses', 10, 'SAR');
        $this->item($expGroup, 'menu.expenses', 'expenses', null, 'manage-expenses', 20, 'SAR');
    }

    // ── 6. System Settings ─────────────────────────────────────────────

    private function seedSystemSettings(int $parentId): void
    {
        // 6.1 Organization — DIRECT for SA
        $this->item($parentId, 'menu.group_organization', 'branches', 'icon-globe-alt', 'view-branches', 10, 'SA');

        // 6.1b Chairs — DIRECT for S only
        $this->item($parentId, 'menu.chairs', 'chairs', 'icon-grid', 'view-chairs', 15, 'S');

        // 6.2 Permissions — GROUP for SA
        $permGroup = $this->item($parentId, 'menu.group_permissions', null, 'icon-lock', null, 20, 'SA');
        $this->item($permGroup, 'menu.system_users', 'users', null, 'view-users', 10, 'S');
        $this->item($permGroup, 'menu.users', 'users', null, 'view-users', 11, 'A');
        $this->item($permGroup, 'menu.roles', 'roles', null, 'manage-roles', 20, 'SA');

        // 6.3 Menu Management — DIRECT for S only
        $this->item($parentId, 'menu.menu_management', 'menu-items', 'icon-layers', 'manage-menu-items', 30, 'S');

        // 6.4 Dictionary Management — DIRECT for SA
        $this->item($parentId, 'menu.dict_items', 'dict-items', 'icon-book-open', 'manage-patient-settings', 35, 'SA');

        // 6.5 System Settings (unified) — DIRECT for SA
        $this->item($parentId, 'menu.software_settings', 'system-settings', 'icon-equalizer', 'manage-settings', 38, 'SA');

        // 6.6 System Maintenance — DIRECT for SA
        $this->item($parentId, 'menu.system_maintenance', 'system-maintenance', 'icon-wrench', 'manage-system-maintenance', 40, 'SA');
    }

    // ── Helper ──────────────────────────────────────────────────────────

    /**
     * 创建一条菜单项 + 角色关联。
     *
     * @param  int|null    $parentId      父级菜单项 ID
     * @param  string      $titleKey      i18n 翻译键
     * @param  string|null $url           URL（null = 目录节点）
     * @param  string|null $icon          图标类
     * @param  string|null $permSlug      权限 slug（null = 不检查）
     * @param  int         $sort          排序值
     * @param  string      $roles         角色缩写字符串，如 'SADNR'
     * @param  array       $urlOverrides  角色专属 URL 覆盖，如 ['D' => 'doctor-claims']
     * @return int                        新建菜单项的 ID
     */
    // ── 诊所事务 ───────────────────────────────────────────────────────────

    private function seedClinicAffairs(int $parentId): void
    {
        $this->item($parentId, 'menu.sterilization_management', 'sterilization', 'icon-shield',
            'view-sterilization', 10, 'SADN');
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    private function item(
        ?int $parentId,
        string $titleKey,
        ?string $url,
        ?string $icon,
        ?string $permSlug,
        int $sort,
        string $roles,
        array $urlOverrides = []
    ): int {
        $permId = $permSlug ? ($this->permIds[$permSlug] ?? null) : null;

        // 声明了权限却查不到，说明 slug 写错或迁移未执行。此时若放任写入 null，
        // 该菜单会变成全员可见 —— 属于静默的越权，必须显式失败。
        if ($permSlug !== null && $permId === null) {
            throw new \RuntimeException(
                "菜单 {$titleKey} 声明的权限 {$permSlug} 在 permissions 表中不存在。"
            );
        }

        // 由 seeder 托管的字段。有意不含 is_active：管理员可能在菜单管理界面
        // 停用过某项，重跑 seeder 不应把它重新打开。
        $attributes = [
            'parent_id'     => $parentId,
            'url'           => $url,
            'icon'          => $icon,
            'permission_id' => $permId,
            'sort_order'    => $sort,
        ];

        $menuItem = MenuItem::where('title_key', $titleKey)->first();

        if ($menuItem) {
            $menuItem->fill($attributes)->save();
            $this->updated++;
        } else {
            $menuItem = MenuItem::create(
                $attributes + ['title_key' => $titleKey, 'is_active' => true]
            );
            $this->created++;
        }

        $this->managedIds[] = $menuItem->id;

        $this->syncRoles($menuItem->id, $roles, $urlOverrides);

        return $menuItem->id;
    }

    /**
     * 幂等同步某菜单项的角色关联，使其恰好等于声明的角色串。
     *
     * 仅作用于本 seeder 托管的菜单项：未托管项的关联不受影响。
     *
     * 注意：role_menu_items 目前是死数据 —— MenuService 只依据
     * menu_items.permission_id 与 roles.hidden_menu_items 过滤侧边栏，
     * 从不读取本表，url_override 同样不生效。此处维持写入是为了在其
     * 去留有定论之前不改变既有语义。
     */
    private function syncRoles(int $menuItemId, string $roles, array $urlOverrides): void
    {
        $roleMap = [
            'S' => 'super-admin',
            'A' => 'admin',
            'D' => 'doctor',
            'N' => 'nurse',
            'R' => 'receptionist',
            'I' => 'inventory-manager',
        ];

        $desired = [];
        foreach (str_split($roles) as $char) {
            $slug = $roleMap[$char] ?? null;
            if ($slug && isset($this->roleIds[$slug])) {
                $desired[$this->roleIds[$slug]] = $urlOverrides[$char] ?? null;
            }
        }

        // 移除声明之外的角色关联（作用域仅限本菜单项）
        DB::table('role_menu_items')
            ->where('menu_item_id', $menuItemId)
            ->whereNotIn('role_id', array_keys($desired) ?: [0])
            ->delete();

        foreach ($desired as $roleId => $override) {
            DB::table('role_menu_items')->updateOrInsert(
                ['role_id' => $roleId, 'menu_item_id' => $menuItemId],
                ['url_override' => $override]
            );
        }
    }
}
