@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('members.page_title') }} / {{ __('members.member_details') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('members') }}" class="btn btn-default">
                        {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    <!-- Member Info Card -->
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">{{ __('members.member_info') }}</h3>
                            </div>
                            <div class="panel-body">
                                <div class="text-center mb-3">
                                    @if($patient->photo)
                                        <img src="{{ asset('storage/'.$patient->photo) }}" class="img-circle" width="100" height="100">
                                    @else
                                        <img src="{{ asset('backend/assets/pages/media/profile/profile_user.jpg') }}" class="img-circle" width="100" height="100">
                                    @endif
                                </div>
                                <table class="table table-condensed">
                                    <tr>
                                        <td><strong>{{ __('members.member_no') }}:</strong></td>
                                        <td>{{ $patient->member_no }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.patient_name') }}:</strong></td>
                                        <td>{{ $patient->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.level') }}:</strong></td>
                                        <td>
                                            @if($patient->memberLevel)
                                                <span class="label" style="background-color:{{ $patient->memberLevel->color }}">{{ $patient->memberLevel->name }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.balance') }}:</strong></td>
                                        <td class="text-success font-bold">{{ number_format($patient->member_balance, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.points') }}:</strong></td>
                                        <td>{{ number_format($patient->member_points) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.total_consumption') }}:</strong></td>
                                        <td>{{ number_format($patient->total_consumption, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.member_since') }}:</strong></td>
                                        <td>{{ $patient->member_since ? \Carbon\Carbon::parse($patient->member_since)->format('Y-m-d') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.expiry_date') }}:</strong></td>
                                        <td>{{ $patient->member_expiry ? \Carbon\Carbon::parse($patient->member_expiry)->format('Y-m-d') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.status') }}:</strong></td>
                                        <td>
                                            @php
                                                $statusClass = 'default';
                                                if($patient->member_status == 'Active') $statusClass = 'success';
                                                elseif($patient->member_status == 'Expired') $statusClass = 'danger';
                                            @endphp
                                            <span class="label label-{{ $statusClass }}">{{ __('members.status_' . strtolower($patient->member_status)) }}</span>
                                        </td>
                                    </tr>
                                </table>
                                <div class="mt-3">
                                    <button class="btn btn-success btn-block" onclick="depositMember({{ $patient->id }})">
                                        <i class="fa fa-plus"></i> {{ __('members.deposit') }}
                                    </button>
                                    <button class="btn btn-primary btn-block" onclick="editMember({{ $patient->id }})">
                                        <i class="fa fa-edit"></i> {{ __('common.edit') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction History -->
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">{{ __('members.transaction_history') }}</h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-striped table-bordered table-hover" id="transactions_table">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('members.transaction_no') }}</th>
                                        <th>{{ __('members.transaction_type') }}</th>
                                        <th>{{ __('members.amount') }}</th>
                                        <th>{{ __('members.balance_after') }}</th>
                                        <th>{{ __('members.payment_method') }}</th>
                                        <th>{{ __('members.date') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

@include('members.edit')
@include('members.deposit')

@endsection
@section('js')
    <script>
        var memberId = {{ $patient->id }};
        var levels = @json($levels);

        LanguageManager.loadAllFromPHP({
            'members': @json(__('members')),
            'messages': @json(__('messages'))
        });
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/members.js') }}"></script>
    <script>
        $(document).ready(function() {
            loadTransactions();
        });
    </script>
@endsection
