{{-- Receptionist Sidebar Menu - Layout4 (3-Level) --}}
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
                        <a href="{{ url('patients') }}" class="nav-link">
                            <i class="icon-list"></i>
                            <span class="title">{{ __('menu.group_patient_management') }}</span>
                        </a>
                    </li>
                    {{-- 2.2 Membership (members + coupons) --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-badge"></i>
                            <span class="title">{{ __('menu.group_member_management') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('members') }}" class="nav-link"><span class="title">{{ __('menu.members_list') }}</span></a></li>
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
            {{-- 3. Clinical Center (Receptionist: appointment scheduling only) --}}
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
                </ul>
            </li>
            {{-- 4. Operations Center (Receptionist: billing + accounts + expenses + payslip + leave) --}}
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
                    {{-- 4.2 Accounts (self-accounts only) --}}
                    <li class="nav-item">
                        <a href="{{ url('self-accounts') }}" class="nav-link">
                            <i class="icon-wallet"></i>
                            <span class="title">{{ __('menu.group_accounts_management') }}</span>
                        </a>
                    </li>
                    {{-- 4.3 Employee (own payslip) --}}
                    <li class="nav-item">
                        <a href="{{ url('individual-payslips') }}" class="nav-link">
                            <i class="icon-briefcase"></i>
                            <span class="title">{{ __('menu.individual_payslip') }}</span>
                        </a>
                    </li>
                    {{-- 4.4 Leave --}}
                    <li class="nav-item">
                        <a href="{{ url('leave-requests') }}" class="nav-link">
                            <i class="icon-calendar"></i>
                            <span class="title">{{ __('menu.leave_requests') }}</span>
                        </a>
                    </li>
                </ul>
            </li>
            {{-- 5. Data Center (Receptionist: expense analysis only) --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-graph"></i>
                    <span class="title">{{ __('menu.data_center') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    {{-- 5.1 Expense Analysis --}}
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
        </ul>
    </div>
</div>
