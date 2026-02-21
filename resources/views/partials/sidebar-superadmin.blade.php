{{-- SuperAdmin Sidebar Menu - Layout4 (3-Level) --}}
<div class="page-sidebar-wrapper">
    <div class="page-sidebar navbar-collapse collapse">
        <ul class="page-sidebar-menu page-header-fixed" data-keep-expanded="false" data-auto-scroll="true" data-slide-speed="200">
            <li class="sidebar-toggler-wrapper hide">
                <div class="sidebar-toggler">
                    <span></span>
                </div>
            </li>
            {{-- Brand --}}
            <li class="sidebar-search-wrapper" style="padding: 15px 18px;">
                <span style="color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 600;">
                    {{ Auth::User()->branch->name ?? config('app.name') }}
                </span>
            </li>
            {{-- 1. Dashboard --}}
            <li class="nav-item start">
                <a href="{{ url('home') }}" class="nav-link">
                    <i class="icon-home"></i>
                    <span class="title">{{ __('menu.dashboard') }}</span>
                </a>
            </li>
            {{-- 2. Patient Center --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-users"></i>
                    <span class="title">{{ __('menu.patient_center') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    {{-- 2.1 Patient Files --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-list"></i>
                            <span class="title">{{ __('menu.group_patient_management') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('patients') }}" class="nav-link"><span class="title">{{ __('menu.patients_list') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('patient-tags') }}" class="nav-link"><span class="title">{{ __('menu.patient_tags') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('patient-sources') }}" class="nav-link"><span class="title">{{ __('menu.patient_sources') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 2.2 Membership --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-badge"></i>
                            <span class="title">{{ __('menu.group_member_management') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('members') }}" class="nav-link"><span class="title">{{ __('menu.members_list') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('member-levels') }}" class="nav-link"><span class="title">{{ __('menu.member_levels') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('coupons') }}" class="nav-link"><span class="title">{{ __('menu.coupons') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 2.3 Patient Care --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-call-out"></i>
                            <span class="title">{{ __('menu.group_patient_care') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('patient-followups') }}" class="nav-link"><span class="title">{{ __('menu.patient_followups') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('birthday-wishes') }}" class="nav-link"><span class="title">{{ __('menu.birthday_wishes') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('satisfaction-surveys') }}" class="nav-link"><span class="title">{{ __('menu.satisfaction_survey') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 2.4 Image Data --}}
                    <li class="nav-item">
                        <a href="{{ url('patient-images') }}" class="nav-link">
                            <i class="icon-picture"></i>
                            <span class="title">{{ __('menu.group_image_data') }}</span>
                        </a>
                    </li>
                </ul>
            </li>
            {{-- 3. Clinical Center --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-briefcase"></i>
                    <span class="title">{{ __('menu.clinical_center') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    {{-- 3.1 Appointment Management --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-calendar"></i>
                            <span class="title">{{ __('menu.group_appointment_management') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('appointments') }}" class="nav-link"><span class="title">{{ __('menu.appointments') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('doctor-schedules') }}" class="nav-link"><span class="title">{{ __('menu.doctor_schedules') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('online-bookings') }}" class="nav-link"><span class="title">{{ __('menu.online_bookings') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('waiting-queue') }}" class="nav-link"><span class="title">{{ __('menu.waiting_queue') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 3.2 Medical Records --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-doc"></i>
                            <span class="title">{{ __('menu.group_medical_records') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('medical-cases') }}" class="nav-link"><span class="title">{{ __('menu.medical_cases') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('dental-charting') }}" class="nav-link"><span class="title">{{ __('menu.dental_charting') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('prescriptions') }}" class="nav-link"><span class="title">{{ __('menu.prescriptions') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 3.3 Treatment Plans --}}
                    <li class="nav-item">
                        <a href="{{ url('treatment-plans') }}" class="nav-link">
                            <i class="icon-list"></i>
                            <span class="title">{{ __('menu.group_treatment_plan') }}</span>
                        </a>
                    </li>
                    {{-- 3.4 Clinical Config --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-wrench"></i>
                            <span class="title">{{ __('menu.group_clinical_config') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('clinic-services') }}" class="nav-link"><span class="title">{{ __('menu.service_items') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('medical-templates') }}" class="nav-link"><span class="title">{{ __('menu.medical_templates') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('quick-phrases') }}" class="nav-link"><span class="title">{{ __('menu.quick_phrases') }}</span></a></li>
                        </ul>
                    </li>
                </ul>
            </li>
            {{-- 4. Operations Center --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-wallet"></i>
                    <span class="title">{{ __('menu.operations_center') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    {{-- 4.1 Billing --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-doc"></i>
                            <span class="title">{{ __('menu.group_billing') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('invoices') }}" class="nav-link"><span class="title">{{ __('menu.invoices') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('quotations') }}" class="nav-link"><span class="title">{{ __('menu.quotations') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('refunds') }}" class="nav-link"><span class="title">{{ __('menu.refunds') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('invoices/pending-discount-approvals') }}" class="nav-link"><span class="title">{{ __('menu.pending_discount_approvals') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.2 Insurance Claims --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-shield"></i>
                            <span class="title">{{ __('menu.group_insurance_claims') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('insurance-companies') }}" class="nav-link"><span class="title">{{ __('menu.insurance_companies') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('claim-rates') }}" class="nav-link"><span class="title">{{ __('menu.claim_rates') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('doctor-claims') }}" class="nav-link"><span class="title">{{ __('menu.doctor_claims') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.3 Accounts --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-wallet"></i>
                            <span class="title">{{ __('menu.group_accounts_management') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('self-accounts') }}" class="nav-link"><span class="title">{{ __('menu.self_accounts') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('charts-of-accounts') }}" class="nav-link"><span class="title">{{ __('menu.charts_of_accounts') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('sms-transactions') }}" class="nav-link"><span class="title">{{ __('menu.sms_credit') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.4 Consumables --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-layers"></i>
                            <span class="title">{{ __('menu.group_consumables') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('stock-ins') }}" class="nav-link"><span class="title">{{ __('inventory.stock_in') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('stock-outs') }}" class="nav-link"><span class="title">{{ __('inventory.stock_out') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('service-consumables') }}" class="nav-link"><span class="title">{{ __('inventory.service_consumables') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('inventory-categories') }}" class="nav-link"><span class="title">{{ __('inventory.categories') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('inventory-items') }}" class="nav-link"><span class="title">{{ __('inventory.items') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.5 Suppliers --}}
                    <li class="nav-item">
                        <a href="{{ url('suppliers') }}" class="nav-link">
                            <i class="icon-handbag"></i>
                            <span class="title">{{ __('menu.group_supplier') }}</span>
                        </a>
                    </li>
                    {{-- 4.6 Lab Case Management (技工单管理) --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-wrench"></i>
                            <span class="title">{{ __('menu.group_lab_management') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('lab-cases') }}" class="nav-link"><span class="title">{{ __('menu.lab_cases') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('labs') }}" class="nav-link"><span class="title">{{ __('menu.labs') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.7 Employees --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-briefcase"></i>
                            <span class="title">{{ __('menu.group_employee') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('employee-contracts') }}" class="nav-link"><span class="title">{{ __('menu.employee_contracts') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('payslips') }}" class="nav-link"><span class="title">{{ __('menu.employee_payslips') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('salary-advances') }}" class="nav-link"><span class="title">{{ __('menu.salary_payment') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.7 Performance --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-calculator"></i>
                            <span class="title">{{ __('menu.group_performance') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('commission-rules') }}" class="nav-link"><span class="title">{{ __('menu.commission_rules') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('doctor-performance-report') }}" class="nav-link"><span class="title">{{ __('menu.doctor_performance_report') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 4.8 Attendance & Leave --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-calendar"></i>
                            <span class="title">{{ __('menu.group_attendance_leave') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('holidays') }}" class="nav-link"><span class="title">{{ __('menu.holidays') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('leave-types') }}" class="nav-link"><span class="title">{{ __('menu.leave_types') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('leave-requests') }}" class="nav-link"><span class="title">{{ __('menu.leave_requests') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('leave-requests-approval') }}" class="nav-link"><span class="title">{{ __('menu.leave_approval') }}</span></a></li>
                        </ul>
                    </li>
                </ul>
            </li>
            {{-- 5. Data Center --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-graph"></i>
                    <span class="title">{{ __('menu.data_center') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    {{-- 5.1 Revenue Analysis --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-bar-chart"></i>
                            <span class="title">{{ __('menu.group_revenue_analysis') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('invoice-payments-report') }}" class="nav-link"><span class="title">{{ __('menu.general_income_report') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('procedure-income-report') }}" class="nav-link"><span class="title">{{ __('menu.procedures_income_report') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('debtors') }}" class="nav-link"><span class="title">{{ __('menu.aged_receivable_report') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('budget-line-report') }}" class="nav-link"><span class="title">{{ __('menu.budget_line_report') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 5.2 Business Analysis --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-pie-chart"></i>
                            <span class="title">{{ __('menu.group_business_analysis') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('revisit-rate-report') }}" class="nav-link"><span class="title">{{ __('menu.revisit_rate_report') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('patient-source-report') }}" class="nav-link"><span class="title">{{ __('menu.patient_source_report') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('insurance-reports') }}" class="nav-link"><span class="title">{{ __('menu.insurance_reports') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 5.3 Expense Analysis --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-basket"></i>
                            <span class="title">{{ __('menu.group_expense_analysis') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('expense-categories') }}" class="nav-link"><span class="title">{{ __('menu.expense_items') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('expenses') }}" class="nav-link"><span class="title">{{ __('menu.expenses') }}</span></a></li>
                        </ul>
                    </li>
                </ul>
            </li>
            {{-- 6. System Settings --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-settings"></i>
                    <span class="title">{{ __('menu.system_settings') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    {{-- 6.1 Organization --}}
                    <li class="nav-item">
                        <a href="{{ url('branches') }}" class="nav-link">
                            <i class="icon-globe-alt"></i>
                            <span class="title">{{ __('menu.group_organization') }}</span>
                        </a>
                    </li>
                    {{-- 6.1b Chairs --}}
                    <li class="nav-item">
                        <a href="{{ url('chairs') }}" class="nav-link">
                            <i class="icon-grid"></i>
                            <span class="title">{{ __('menu.chairs') }}</span>
                        </a>
                    </li>
                    {{-- 6.2 Permissions --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-lock"></i>
                            <span class="title">{{ __('menu.group_permissions') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('users') }}" class="nav-link"><span class="title">{{ __('menu.system_users') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('roles') }}" class="nav-link"><span class="title">{{ __('menu.roles') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('role-permissions') }}" class="nav-link"><span class="title">{{ __('menu.role_permissions') }}</span></a></li>
                        </ul>
                    </li>
                    {{-- 6.3 System Maintenance --}}
                    @can('manage-system-maintenance')
                    <li class="nav-item">
                        <a href="{{ url('system-maintenance') }}" class="nav-link">
                            <i class="icon-wrench"></i>
                            <span class="title">{{ __('menu.system_maintenance') }}</span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
        </ul>
    </div>
</div>
