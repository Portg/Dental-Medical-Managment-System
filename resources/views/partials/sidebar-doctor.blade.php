{{-- Doctor Sidebar Menu - Layout4 (3-Level) --}}
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
                    {{-- 2.1 Patient Files (patients only) --}}
                    <li class="nav-item">
                        <a href="{{ url('patients') }}" class="nav-link">
                            <i class="icon-list"></i>
                            <span class="title">{{ __('menu.group_patient_management') }}</span>
                        </a>
                    </li>
                    {{-- 2.2 Membership (members list only) --}}
                    <li class="nav-item">
                        <a href="{{ url('members') }}" class="nav-link">
                            <i class="icon-badge"></i>
                            <span class="title">{{ __('menu.group_member_management') }}</span>
                        </a>
                    </li>
                    {{-- 2.3 Image Data --}}
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
                            <li class="nav-item"><a href="{{ url('doctor-queue') }}" class="nav-link"><span class="title">{{ __('menu.doctor_queue') }}</span></a></li>
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
                    {{-- 3.4 Clinical Config (templates & phrases) --}}
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle">
                            <i class="icon-wrench"></i>
                            <span class="title">{{ __('menu.group_clinical_config') }}</span>
                            <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item"><a href="{{ url('medical-templates') }}" class="nav-link"><span class="title">{{ __('menu.medical_templates') }}</span></a></li>
                            <li class="nav-item"><a href="{{ url('quick-phrases') }}" class="nav-link"><span class="title">{{ __('menu.quick_phrases') }}</span></a></li>
                        </ul>
                    </li>
                </ul>
            </li>
            {{-- 4. Operations Center (Doctor: billing + claims + payslip + performance + leave) --}}
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
                    {{-- 4.2 Insurance Claims (claim submission only) --}}
                    <li class="nav-item">
                        <a href="{{ url('doctor-claims') }}" class="nav-link">
                            <i class="icon-shield"></i>
                            <span class="title">{{ __('menu.group_insurance_claims') }}</span>
                        </a>
                    </li>
                    {{-- 4.3 Employee (own payslip) --}}
                    <li class="nav-item">
                        <a href="{{ url('individual-payslips') }}" class="nav-link">
                            <i class="icon-briefcase"></i>
                            <span class="title">{{ __('menu.individual_payslip') }}</span>
                        </a>
                    </li>
                    {{-- 4.4 Performance (own stats) --}}
                    <li class="nav-item">
                        <a href="{{ url('doctor-performance-report') }}" class="nav-link">
                            <i class="icon-calculator"></i>
                            <span class="title">{{ __('menu.group_performance') }}</span>
                        </a>
                    </li>
                    {{-- 4.5 Leave --}}
                    <li class="nav-item">
                        <a href="{{ url('leave-requests') }}" class="nav-link">
                            <i class="icon-calendar"></i>
                            <span class="title">{{ __('menu.leave_requests') }}</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>
