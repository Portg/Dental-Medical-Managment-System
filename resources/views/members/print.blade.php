<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('members.print_member_info') }}</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .card {
            border: 2px solid #333;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            margin: 0 auto;
        }
        .card-header {
            text-align: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .card-header h2 {
            margin: 0;
            color: #333;
        }
        .card-header .level-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            color: #fff;
            margin-top: 8px;
            font-size: 14px;
        }
        .card-body table {
            width: 100%;
        }
        .card-body td {
            padding: 6px 10px;
            vertical-align: top;
        }
        .card-body .label {
            font-weight: bold;
            color: #666;
            width: 40%;
        }
        .card-body .value {
            color: #333;
        }
        .card-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 12px;
            color: #999;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        @@media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h2>{{ __('members.print_member_info') }}</h2>
            @if($patient->memberLevel)
                <span class="level-badge" style="background-color:{{ $patient->memberLevel->color }}">
                    {{ $patient->memberLevel->name }}
                </span>
            @endif
        </div>
        <div class="card-body">
            <table>
                <tr>
                    <td class="label">{{ __('members.member_no') }}:</td>
                    <td class="value">{{ $patient->member_no }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('members.patient_name') }}:</td>
                    <td class="value">{{ $patient->full_name }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('members.level') }}:</td>
                    <td class="value">{{ $patient->memberLevel->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('members.balance') }}:</td>
                    <td class="value">{{ number_format($patient->member_balance, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('members.points') }}:</td>
                    <td class="value">{{ number_format($patient->member_points) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('members.member_since') }}:</td>
                    <td class="value">{{ $patient->member_since ? \Carbon\Carbon::parse($patient->member_since)->format('Y-m-d') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('members.expiry_date') }}:</td>
                    <td class="value">{{ $patient->member_expiry ? \Carbon\Carbon::parse($patient->member_expiry)->format('Y-m-d') : '-' }}</td>
                </tr>
            </table>
        </div>
        <div class="card-footer">
            {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 30px; font-size:16px; cursor:pointer;">
            {{ __('members.print_card') }}
        </button>
        <button onclick="window.close()" style="padding:10px 30px; font-size:16px; cursor:pointer; margin-left:10px;">
            {{ __('common.close') }}
        </button>
    </div>
</body>
</html>
