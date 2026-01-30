{{-- Admin Sidebar Menu - Layout4 --}}
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
                    <li class="nav-item"><a href="{{ url('patients') }}" class="nav-link"><i class="icon-list"></i><span class="title">{{ __('menu.patients_list') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('patient-tags') }}" class="nav-link"><i class="icon-tag"></i><span class="title">{{ __('menu.patient_tags') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('patient-sources') }}" class="nav-link"><i class="icon-share"></i><span class="title">{{ __('menu.patient_sources') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('patient-images') }}" class="nav-link"><i class="icon-picture"></i><span class="title">{{ __('menu.patient_images') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('patient-followups') }}" class="nav-link"><i class="icon-call-out"></i><span class="title">{{ __('menu.patient_followups') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('birthday-wishes') }}" class="nav-link"><i class="icon-present"></i><span class="title">{{ __('menu.birthday_wishes') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('members') }}" class="nav-link"><i class="icon-badge"></i><span class="title">{{ __('menu.members_list') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('member-levels') }}" class="nav-link"><i class="icon-diamond"></i><span class="title">{{ __('menu.member_levels') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('satisfaction-surveys') }}" class="nav-link"><i class="icon-emotsmile"></i><span class="title">{{ __('menu.satisfaction_survey') }}</span></a></li>
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
                    <li class="nav-item"><a href="{{ url('appointments') }}" class="nav-link"><i class="icon-calendar"></i><span class="title">{{ __('menu.appointments') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('doctor-schedules') }}" class="nav-link"><i class="icon-clock"></i><span class="title">{{ __('menu.doctor_schedules') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('waiting-queue') }}" class="nav-link"><i class="icon-people"></i><span class="title">{{ __('menu.waiting_queue') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('online-bookings') }}" class="nav-link"><i class="icon-globe"></i><span class="title">{{ __('menu.online_bookings') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('medical-cases') }}" class="nav-link"><i class="icon-doc"></i><span class="title">{{ __('menu.medical_cases') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('dental-charting') }}" class="nav-link"><i class="icon-organization"></i><span class="title">{{ __('menu.dental_charting') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('treatment-plans') }}" class="nav-link"><i class="icon-list"></i><span class="title">{{ __('menu.treatment_plans') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('prescriptions') }}" class="nav-link"><i class="icon-chemistry"></i><span class="title">{{ __('menu.prescriptions') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('medical-templates') }}" class="nav-link"><i class="icon-notebook"></i><span class="title">{{ __('menu.medical_templates') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('quick-phrases') }}" class="nav-link"><i class="icon-speech"></i><span class="title">{{ __('menu.quick_phrases') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('clinic-services') }}" class="nav-link"><i class="icon-wrench"></i><span class="title">{{ __('menu.service_items') }}</span></a></li>
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
                    <li class="nav-item"><a href="{{ url('invoices') }}" class="nav-link"><i class="icon-doc"></i><span class="title">{{ __('menu.invoices') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('quotations') }}" class="nav-link"><i class="icon-docs"></i><span class="title">{{ __('menu.quotations') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('refunds') }}" class="nav-link"><i class="icon-action-undo"></i><span class="title">{{ __('menu.refunds') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('invoices/pending-discount-approvals') }}" class="nav-link"><i class="icon-check"></i><span class="title">{{ __('menu.pending_discount_approvals') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('coupons') }}" class="nav-link"><i class="icon-present"></i><span class="title">{{ __('menu.coupons') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('self-accounts') }}" class="nav-link"><i class="icon-wallet"></i><span class="title">{{ __('menu.self_accounts') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('insurance-companies') }}" class="nav-link"><i class="icon-shield"></i><span class="title">{{ __('menu.insurance_companies') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('inventory-categories') }}" class="nav-link"><i class="icon-grid"></i><span class="title">{{ __('inventory.categories') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('inventory-items') }}" class="nav-link"><i class="icon-layers"></i><span class="title">{{ __('inventory.items') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('stock-ins') }}" class="nav-link"><i class="icon-arrow-down"></i><span class="title">{{ __('inventory.stock_in') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('stock-outs') }}" class="nav-link"><i class="icon-arrow-up"></i><span class="title">{{ __('inventory.stock_out') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('service-consumables') }}" class="nav-link"><i class="icon-link"></i><span class="title">{{ __('inventory.service_consumables') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('employee-contracts') }}" class="nav-link"><i class="icon-briefcase"></i><span class="title">{{ __('menu.employee_contracts') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('payslips') }}" class="nav-link"><i class="icon-wallet"></i><span class="title">{{ __('menu.employee_payslips') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('salary-advances') }}" class="nav-link"><i class="icon-credit-card"></i><span class="title">{{ __('menu.salary_payment') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('holidays') }}" class="nav-link"><i class="icon-calendar"></i><span class="title">{{ __('menu.holidays') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('leave-types') }}" class="nav-link"><i class="icon-list"></i><span class="title">{{ __('menu.leave_types') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('leave-requests') }}" class="nav-link"><i class="icon-paper-plane"></i><span class="title">{{ __('menu.leave_requests') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('leave-requests-approval') }}" class="nav-link"><i class="icon-check"></i><span class="title">{{ __('menu.leave_approval') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('commission-rules') }}" class="nav-link"><i class="icon-calculator"></i><span class="title">{{ __('menu.commission_rules') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('sms-transactions') }}" class="nav-link"><i class="icon-envelope"></i><span class="title">{{ __('menu.sms_credit') }}</span></a></li>
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
                    <li class="nav-item"><a href="{{ url('invoice-payments-report') }}" class="nav-link"><i class="icon-chart"></i><span class="title">{{ __('menu.general_income_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('doctor-performance-report') }}" class="nav-link"><i class="icon-user"></i><span class="title">{{ __('menu.doctor_performance_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('procedure-income-report') }}" class="nav-link"><i class="icon-pie-chart"></i><span class="title">{{ __('menu.procedures_income_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('debtors') }}" class="nav-link"><i class="icon-exclamation"></i><span class="title">{{ __('menu.aged_receivable_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('patient-source-report') }}" class="nav-link"><i class="icon-share"></i><span class="title">{{ __('menu.patient_source_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('revisit-rate-report') }}" class="nav-link"><i class="icon-action-redo"></i><span class="title">{{ __('menu.revisit_rate_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('insurance-reports') }}" class="nav-link"><i class="icon-shield"></i><span class="title">{{ __('menu.insurance_reports') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('budget-line-report') }}" class="nav-link"><i class="icon-bar-chart"></i><span class="title">{{ __('menu.budget_line_report') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('expense-categories') }}" class="nav-link"><i class="icon-folder"></i><span class="title">{{ __('menu.expense_items') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('suppliers') }}" class="nav-link"><i class="icon-people"></i><span class="title">{{ __('menu.suppliers') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('expenses') }}" class="nav-link"><i class="icon-basket"></i><span class="title">{{ __('menu.expenses') }}</span></a></li>
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
                    <li class="nav-item"><a href="{{ url('branches') }}" class="nav-link"><i class="icon-organization"></i><span class="title">{{ __('menu.branches') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('users') }}" class="nav-link"><i class="icon-user"></i><span class="title">{{ __('menu.users') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('roles') }}" class="nav-link"><i class="icon-lock"></i><span class="title">{{ __('menu.roles') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('role-permissions') }}" class="nav-link"><i class="icon-key"></i><span class="title">{{ __('menu.role_permissions') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('charts-of-accounts') }}" class="nav-link"><i class="icon-book-open"></i><span class="title">{{ __('menu.charts_of_accounts') }}</span></a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
