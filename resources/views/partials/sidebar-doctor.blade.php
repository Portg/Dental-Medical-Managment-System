{{-- Doctor Sidebar Menu - Layout4 --}}
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
                    <li class="nav-item"><a href="{{ url('patients') }}" class="nav-link"><i class="icon-people"></i><span class="title">{{ __('menu.patients_list') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('patient-images') }}" class="nav-link"><i class="icon-picture"></i><span class="title">{{ __('menu.patient_images') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('members') }}" class="nav-link"><i class="icon-badge"></i><span class="title">{{ __('menu.members_list') }}</span></a></li>
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
                    <li class="nav-item"><a href="{{ url('appointments') }}" class="nav-link"><i class="icon-clock"></i><span class="title">{{ __('menu.appointments') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('doctor-queue') }}" class="nav-link"><i class="icon-people"></i><span class="title">{{ __('menu.doctor_queue') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('medical-cases') }}" class="nav-link"><i class="icon-doc"></i><span class="title">{{ __('menu.medical_cases') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('treatment-plans') }}" class="nav-link"><i class="icon-list"></i><span class="title">{{ __('menu.treatment_plans') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('prescriptions') }}" class="nav-link"><i class="icon-note"></i><span class="title">{{ __('menu.prescriptions') }}</span></a></li>
                </ul>
            </li>
            {{-- 4. Finance --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-wallet"></i>
                    <span class="title">{{ __('menu.finance') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    <li class="nav-item"><a href="{{ url('invoices') }}" class="nav-link"><i class="icon-doc"></i><span class="title">{{ __('menu.invoices') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('quotations') }}" class="nav-link"><i class="icon-docs"></i><span class="title">{{ __('menu.quotations') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('refunds') }}" class="nav-link"><i class="icon-action-undo"></i><span class="title">{{ __('menu.refunds') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('invoices/pending-discount-approvals') }}" class="nav-link"><i class="icon-check"></i><span class="title">{{ __('menu.pending_discount_approvals') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('doctor-claims') }}" class="nav-link"><i class="icon-badge"></i><span class="title">{{ __('menu.doctor_claims') }}</span></a></li>
                </ul>
            </li>
            {{-- 5. Leave Requests --}}
            <li class="nav-item">
                <a href="{{ url('leave-requests') }}" class="nav-link">
                    <i class="icon-calendar"></i>
                    <span class="title">{{ __('menu.leave_requests') }}</span>
                </a>
            </li>
            {{-- 6. Settings --}}
            <li class="nav-item">
                <a href="javascript:;" class="nav-link nav-toggle">
                    <i class="icon-settings"></i>
                    <span class="title">{{ __('menu.settings') }}</span>
                    <span class="arrow"></span>
                </a>
                <ul class="sub-menu">
                    <li class="nav-item"><a href="{{ url('medical-templates') }}" class="nav-link"><i class="icon-notebook"></i><span class="title">{{ __('menu.medical_templates') }}</span></a></li>
                    <li class="nav-item"><a href="{{ url('quick-phrases') }}" class="nav-link"><i class="icon-speech"></i><span class="title">{{ __('menu.quick_phrases') }}</span></a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
