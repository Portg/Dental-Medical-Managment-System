<?php

namespace App\Exports;

use App\Exports\Sheets\SimpleTableSheet;
use App\Services\DataMaskingService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 复诊率统计报表导出。
 *
 * 分五个工作表：概览 / 月度趋势 / 医生复诊统计 / 复诊间隔分布 / 流失患者。
 *
 * 流失患者表含患者档案，按 AG-045 处理：
 * 不导出 NIN（身份证号），手机号脱敏为 138****1234。
 */
class RevisitRateExport implements WithMultipleSheets
{
    private array $data;
    private string $startDate;
    private string $endDate;

    public function __construct(array $data, string $startDate, string $endDate)
    {
        $this->data      = $data;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function sheets(): array
    {
        return [
            $this->overviewSheet(),
            $this->monthlyTrendSheet(),
            $this->doctorStatsSheet(),
            $this->intervalSheet(),
            $this->lostPatientsSheet(),
        ];
    }

    private function overviewSheet(): SimpleTableSheet
    {
        $rows = [
            [__('report.date_range'), $this->startDate . ' ~ ' . $this->endDate],
            [__('report.current_period_patients'), (int) ($this->data['currentPeriodPatients'] ?? 0)],
            [__('report.first_visit_patients'), (int) ($this->data['firstVisitPatients'] ?? 0)],
            [__('report.revisit_patients'), (int) ($this->data['revisitPatients'] ?? 0)],
            [__('report.revisit_rate'), ($this->data['revisitRate'] ?? 0) . '%'],
        ];

        return new SimpleTableSheet(
            __('report.overview'),
            [__('report.metric'), __('report.value')],
            $rows
        );
    }

    private function monthlyTrendSheet(): SimpleTableSheet
    {
        $rows = [];
        foreach ($this->data['monthlyTrend'] ?? [] as $item) {
            $rows[] = [
                data_get($item, 'month_label', data_get($item, 'month', '')),
                (int) data_get($item, 'total_patients', 0),
                (int) data_get($item, 'first_visit', 0),
                (int) data_get($item, 'revisit', 0),
                data_get($item, 'revisit_rate', 0) . '%',
            ];
        }

        return new SimpleTableSheet(
            __('report.monthly_trend'),
            [
                __('report.month'),
                __('report.total_patients'),
                __('report.first_visit_patients'),
                __('report.revisit_patients'),
                __('report.revisit_rate'),
            ],
            $rows
        );
    }

    private function doctorStatsSheet(): SimpleTableSheet
    {
        $rows = [];
        foreach ($this->data['doctorStats'] ?? [] as $item) {
            $rows[] = [
                data_get($item, 'doctor_name', ''),
                (int) data_get($item, 'total_patients', 0),
                (int) data_get($item, 'total_appointments', 0),
                data_get($item, 'avg_visits_per_patient', 0),
            ];
        }

        return new SimpleTableSheet(
            __('report.doctor_revisit_stats'),
            [
                __('report.doctor'),
                __('report.total_patients'),
                __('report.total_appointments'),
                __('report.avg_visits_per_patient'),
            ],
            $rows
        );
    }

    private function intervalSheet(): SimpleTableSheet
    {
        $rows = [];
        $total = 0;
        foreach ($this->data['intervalDistribution'] ?? [] as $item) {
            $total += (int) data_get($item, 'count', 0);
        }

        foreach ($this->data['intervalDistribution'] ?? [] as $item) {
            $count = (int) data_get($item, 'count', 0);
            $rows[] = [
                data_get($item, 'label', ''),
                data_get($item, 'range', ''),
                $count,
                $total > 0 ? round($count / $total * 100, 1) . '%' : '0%',
            ];
        }

        return new SimpleTableSheet(
            __('report.revisit_interval_distribution'),
            [__('report.interval'), __('report.days'), __('report.patient_count'), __('report.percentage')],
            $rows
        );
    }

    private function lostPatientsSheet(): SimpleTableSheet
    {
        // AG-045：导出不得包含 NIN，手机号必须脱敏
        $mask = DataMaskingService::isExportMaskingEnabled();
        $rows = [];

        foreach ($this->data['lostPatients'] ?? [] as $patient) {
            $fullName = trim((data_get($patient, 'surname', '') . ' ' . data_get($patient, 'othername', '')));
            $phone    = data_get($patient, 'phone_no', '');

            $rows[] = [
                data_get($patient, 'patient_no', ''),
                $mask ? DataMaskingService::maskName($fullName) : $fullName,
                $mask ? DataMaskingService::maskPhone($phone) : $phone,
                data_get($patient, 'last_visit_date', ''),
                (int) data_get($patient, 'days_since_visit', 0),
            ];
        }

        return new SimpleTableSheet(
            __('report.lost_patients'),
            [
                __('report.patient_no'),
                __('report.patient_name'),
                __('report.phone'),
                __('report.last_visit_date'),
                __('report.days_since_last_visit'),
            ],
            $rows
        );
    }
}
