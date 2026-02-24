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
     * 角色缩写：S=super-admin, A=admin, D=doctor, N=nurse, R=receptionist
     */
    private array $roleIds = [];
    private array $permIds = [];

    public function run(): void
    {
        // 清空旧数据
        DB::table('role_menu_items')->truncate();
        DB::table('menu_items')->truncate();

        // 缓存角色和权限 ID
        $this->roleIds = Role::pluck('id', 'slug')->toArray();
        $this->permIds = Permission::pluck('id', 'slug')->toArray();

        $this->seedMenuTree();

        // 清除菜单缓存，确保侧边栏立即生效
        \Illuminate\Support\Facades\Cache::forget('menu_tree:all');
    }

    private function seedMenuTree(): void
    {
        // ── Level 1: Top-level sections ────────────────────────────────

        $dashboard = $this->item(null, 'menu.dashboard', 'home', 'icon-home', null, 10, 'SADNR');

        $patientCenter = $this->item(null, 'menu.patient_center', null, 'icon-users', null, 20, 'SADNR');
        $this->seedPatientCenter($patientCenter);

        $clinicalCenter = $this->item(null, 'menu.clinical_center', null, 'icon-briefcase', null, 30, 'SADNR');
        $this->seedClinicalCenter($clinicalCenter);

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
        $this->item($careGroup, 'menu.satisfaction_survey', 'satisfaction-surveys', null, 'view-patients', 30, 'SAR');

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
    }

    // ── 4. Operations Center ───────────────────────────────────────────

    private function seedOperationsCenter(int $parentId): void
    {
        // 4.1 Billing — GROUP for all 5 roles, same 4 children
        $billGroup = $this->item($parentId, 'menu.group_billing', null, 'icon-doc', null, 10, 'SADNR');
        $this->item($billGroup, 'menu.invoices', 'invoices', null, 'view-invoices', 10, 'SADNR');
        $this->item($billGroup, 'menu.quotations', 'quotations', null, 'manage-quotations', 20, 'SADNR');
        $this->item($billGroup, 'menu.refunds', 'refunds', null, 'manage-refunds', 30, 'SADNR');
        $this->item($billGroup, 'menu.pending_discount_approvals', 'invoices/pending-discount-approvals', null, 'view-invoices', 40, 'SADNR');

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
        $conGroup = $this->item($parentId, 'menu.group_consumables', null, 'icon-layers', null, 40, 'SAN');
        $this->item($conGroup, 'inventory.stock_in', 'stock-ins', null, 'manage-inventory', 10, 'SAN');
        $this->item($conGroup, 'inventory.stock_out', 'stock-outs', null, 'manage-inventory', 20, 'SAN');
        $this->item($conGroup, 'inventory.service_consumables', 'service-consumables', null, 'manage-inventory', 30, 'SAN');
        $this->item($conGroup, 'inventory.categories', 'inventory-categories', null, 'manage-inventory', 40, 'SA');
        $this->item($conGroup, 'inventory.items', 'inventory-items', null, 'manage-inventory', 50, 'SA');

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
        $perfGroup = $this->item($parentId, 'menu.group_performance', 'doctor-performance-report', 'icon-calculator', 'view-reports', 80, 'SAD');
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
        $this->item($revGroup, 'menu.general_income_report', 'invoice-payments-report', null, 'view-reports', 10, 'SA');
        $this->item($revGroup, 'menu.procedures_income_report', 'procedure-income-report', null, 'view-reports', 20, 'SA');
        $this->item($revGroup, 'menu.aged_receivable_report', 'debtors', null, 'view-reports', 30, 'SA');

        // 5.2 Business Analysis — GROUP for SA
        $bizGroup = $this->item($parentId, 'menu.group_business_analysis', null, 'icon-pie-chart', null, 20, 'SA');
        $this->item($bizGroup, 'menu.revisit_rate_report', 'revisit-rate-report', null, 'view-reports', 10, 'SA');
        $this->item($bizGroup, 'menu.patient_source_report', 'patient-source-report', null, 'view-reports', 20, 'SA');
        $this->item($bizGroup, 'menu.appointment_analytics_report', 'appointment-analytics-report', null, 'view-reports', 30, 'SA');
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

        // 6.3 System Maintenance — DIRECT for SA
        $this->item($parentId, 'menu.system_maintenance', 'system-maintenance', 'icon-wrench', 'manage-system-maintenance', 30, 'SA');

        // 6.4 Menu Management — DIRECT for S only
        $this->item($parentId, 'menu.menu_management', 'menu-items', 'icon-layers', 'manage-menu-items', 40, 'S');
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

        $menuItem = MenuItem::create([
            'parent_id'     => $parentId,
            'title_key'     => $titleKey,
            'url'           => $url,
            'icon'          => $icon,
            'permission_id' => $permId,
            'sort_order'    => $sort,
            'is_active'     => true,
        ]);

        // 解析角色缩写并创建关联
        $roleMap = [
            'S' => 'super-admin',
            'A' => 'admin',
            'D' => 'doctor',
            'N' => 'nurse',
            'R' => 'receptionist',
        ];

        $pivotRows = [];
        foreach (str_split($roles) as $char) {
            $slug = $roleMap[$char] ?? null;
            if ($slug && isset($this->roleIds[$slug])) {
                $pivotRows[] = [
                    'role_id'      => $this->roleIds[$slug],
                    'menu_item_id' => $menuItem->id,
                    'url_override' => $urlOverrides[$char] ?? null,
                ];
            }
        }

        if ($pivotRows) {
            DB::table('role_menu_items')->insert($pivotRows);
        }

        return $menuItem->id;
    }
}
