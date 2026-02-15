@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')

<div class="note note-success">
    <p class="text-black-50">
        <a href="{{ url('lab-cases') }}" class="text-primary">
            <i class="fa fa-arrow-left"></i> {{ __('lab_cases.lab_case_list') }}
        </a>
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">
                        {{ __('lab_cases.lab_case_details') }} — {{ $labCase->lab_case_no }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ url('print-lab-case/' . $labCase->id) }}" class="btn grey-salsa btn-sm" target="_blank">
                        <i class="fa fa-print"></i> {{ __('lab_cases.print_lab_case') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover">
                    <tr>
                        <td style="width:30%"><strong>{{ __('lab_cases.lab_case_no') }}</strong></td>
                        <td>{{ $labCase->lab_case_no }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.patient') }}</strong></td>
                        <td>
                            @if($labCase->patient)
                                <a href="{{ url('patients/' . $labCase->patient_id) }}">
                                    {{ \App\Http\Helper\NameHelper::join($labCase->patient->surname, $labCase->patient->othername) }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.doctor') }}</strong></td>
                        <td>{{ $labCase->doctor->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.lab') }}</strong></td>
                        <td>{{ $labCase->lab->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.prosthesis_type') }}</strong></td>
                        <td>{{ __('lab_cases.type_' . $labCase->prosthesis_type) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.material') }}</strong></td>
                        <td>{{ $labCase->material ? __('lab_cases.material_' . $labCase->material) : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.color_shade') }}</strong></td>
                        <td>{{ $labCase->color_shade ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.teeth_positions') }}</strong></td>
                        <td>
                            @if($labCase->teeth_positions)
                                {{ is_array($labCase->teeth_positions) ? implode(', ', $labCase->teeth_positions) : $labCase->teeth_positions }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.special_requirements') }}</strong></td>
                        <td>{{ $labCase->special_requirements ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.notes') }}</strong></td>
                        <td>{{ $labCase->notes ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- Status --}}
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold">{{ __('lab_cases.status') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                @php
                    $badges = [
                        'pending' => 'default', 'sent' => 'info', 'in_production' => 'warning',
                        'returned' => 'primary', 'try_in' => 'info', 'completed' => 'success', 'rework' => 'danger',
                    ];
                @endphp
                <h3>
                    <span class="label label-{{ $badges[$labCase->status] ?? 'default' }} label-lg">
                        {{ __('lab_cases.status_' . $labCase->status) }}
                    </span>
                    @if($labCase->is_overdue)
                        <span class="text-danger"><i class="fa fa-exclamation-triangle"></i> {{ __('lab_cases.overdue') }}</span>
                    @endif
                </h3>

                @if($labCase->rework_count > 0)
                    <p><strong>{{ __('lab_cases.rework_count') }}:</strong> {{ $labCase->rework_count }}</p>
                @endif
                @if($labCase->rework_reason)
                    <p><strong>{{ __('lab_cases.rework_reason') }}:</strong> {{ $labCase->rework_reason }}</p>
                @endif
                @if($labCase->quality_rating)
                    <p><strong>{{ __('lab_cases.quality_rating') }}:</strong> {{ $labCase->quality_rating }} / 5</p>
                @endif
            </div>
        </div>

        {{-- Dates --}}
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold">{{ __('lab_cases.sent_date') }} / {{ __('lab_cases.expected_return_date') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table">
                    <tr>
                        <td><strong>{{ __('lab_cases.sent_date') }}</strong></td>
                        <td>{{ $labCase->sent_date ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.expected_return_date') }}</strong></td>
                        <td>{{ $labCase->expected_return_date ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.actual_return_date') }}</strong></td>
                        <td>{{ $labCase->actual_return_date ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.created_at') }}</strong></td>
                        <td>{{ $labCase->created_at }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Fees --}}
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold">{{ __('lab_cases.lab_fee') }} / {{ __('lab_cases.patient_charge') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table">
                    <tr>
                        <td><strong>{{ __('lab_cases.lab_fee') }}</strong></td>
                        <td>{{ $labCase->lab_fee ? number_format($labCase->lab_fee, 2) : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.patient_charge') }}</strong></td>
                        <td>{{ $labCase->patient_charge ? number_format($labCase->patient_charge, 2) : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('lab_cases.profit') }}</strong></td>
                        <td>
                            @if($labCase->lab_fee !== null && $labCase->patient_charge !== null)
                                <span class="{{ $labCase->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($labCase->profit, 2) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
