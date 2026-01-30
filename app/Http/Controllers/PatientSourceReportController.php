<?php

namespace App\Http\Controllers;

use App\Patient;
use App\PatientSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientSourceReportController extends Controller
{
    /**
     * 患者来源分析报表
     */
    public function index(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // 获取所有患者来源
        $sources = PatientSource::where('is_active', true)->get();

        // 按来源统计患者数量
        $sourceStats = Patient::select('source_id', DB::raw('COUNT(*) as patient_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('source_id')
            ->groupBy('source_id')
            ->get()
            ->keyBy('source_id');

        // 未知来源的患者
        $unknownSourceCount = Patient::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('source_id')
            ->count();

        // 总患者数
        $totalPatients = Patient::whereBetween('created_at', [$startDate, $endDate])->count();

        // 按月份统计趋势
        $monthlyTrend = Patient::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                'source_id',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate->copy()->subMonths(5), $endDate])
            ->groupBy('month', 'source_id')
            ->orderBy('month')
            ->get();

        // 转化率分析（有预约的患者/总患者）
        $conversionStats = Patient::select('source_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('appointments')
            ->whereNotNull('source_id')
            ->groupBy('source_id')
            ->get()
            ->keyBy('source_id');

        // 构建来源分析数据
        $sourceAnalysis = [];
        foreach ($sources as $source) {
            $patientCount = $sourceStats->get($source->id)->patient_count ?? 0;
            $convertedCount = $conversionStats->get($source->id)->total ?? 0;
            $conversionRate = $patientCount > 0 ? round(($convertedCount / $patientCount) * 100, 1) : 0;
            $percentage = $totalPatients > 0 ? round(($patientCount / $totalPatients) * 100, 1) : 0;

            $sourceAnalysis[] = [
                'id' => $source->id,
                'name' => $source->name,
                'color' => $source->color ?? '#3949AB',
                'patient_count' => $patientCount,
                'percentage' => $percentage,
                'converted_count' => $convertedCount,
                'conversion_rate' => $conversionRate,
            ];
        }

        // 添加未知来源
        if ($unknownSourceCount > 0) {
            $sourceAnalysis[] = [
                'id' => 0,
                'name' => __('report.unknown_source'),
                'color' => '#9E9E9E',
                'patient_count' => $unknownSourceCount,
                'percentage' => $totalPatients > 0 ? round(($unknownSourceCount / $totalPatients) * 100, 1) : 0,
                'converted_count' => 0,
                'conversion_rate' => 0,
            ];
        }

        // 按患者数量排序
        usort($sourceAnalysis, function($a, $b) {
            return $b['patient_count'] - $a['patient_count'];
        });

        return view('reports.patient_source_report', compact(
            'sourceAnalysis',
            'totalPatients',
            'monthlyTrend',
            'startDate',
            'endDate',
            'sources'
        ));
    }

    /**
     * 导出报表
     */
    public function export(Request $request)
    {
        // TODO: Implement Excel export
    }
}
