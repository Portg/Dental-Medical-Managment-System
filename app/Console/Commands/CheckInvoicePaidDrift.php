<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 体检 invoices.paid_amount 与收款/退费明细是否对得上。
 *
 * 背景：paid_amount 是存储列，outstanding_amount 与 payment_status 都由
 * Invoice::boot() 的 saving 钩子据它派生。此前只有 processMixedPayment() 会更新
 * 它，走 /payments 的单笔收款、改金额、撤销收款都不碰 —— 于是存储值可能和明细
 * 对不上。现在 InvoicePaymentService::syncInvoicePaidAmount() 改成按明细重算：
 *
 *     paid_amount = 收款合计 − 已通过退费合计
 *
 * 重算只在有人动那张账单的收款时触发。若库里存在历史漂移，第一次动它就会被
 * 「纠正」—— 如果那笔钱其实真收到了、只是明细行丢了，纠正就等于抹账。
 * 所以上线前先用这条命令看看有没有、有多少。
 *
 * 本命令只读，不写任何数据。
 *
 * 输出刻意用英文：Windows 控制台默认 GBK 代码页，输出 UTF-8 中文会变乱码。
 * 需要中文报告时用 --out= 导出到文件（文件是 UTF-8，用编辑器打开即可）。
 */
class CheckInvoicePaidDrift extends Command
{
    protected $signature = 'invoices:check-paid-drift
        {--limit=50 : Max rows to print (0 = print all)}
        {--json : Output machine-readable JSON instead of a table}
        {--out= : Also write the full report to this file (UTF-8)}';

    protected $description = 'Read-only check: does invoices.paid_amount match its payment/refund detail rows?';

    /** 金额比较容差：分以下的差异当作相等，避免 double 累加的尾数噪音 */
    private const TOLERANCE = '0.01';

    public function handle(): int
    {
        $totalInvoices = (int) DB::table('invoices')->whereNull('deleted_at')->count();

        if ($totalInvoices === 0) {
            $this->warn('No invoices found in database "' . DB::connection()->getDatabaseName() . '".');
            $this->warn('Nothing to check - this result says nothing about other environments.');

            return self::SUCCESS;
        }

        $rows = $this->findDrift();

        $report = [
            'database'       => DB::connection()->getDatabaseName(),
            'total_invoices' => $totalInvoices,
            'drift_count'    => count($rows),
            'drift_total'    => $this->sumAbsoluteDrift($rows),
            'rows'           => $rows,
        ];

        if ($this->option('out')) {
            $this->writeReport($report);
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return count($rows) === 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->printTable($report);

        // 有漂移时返回非零，方便在部署脚本里当作一道门。检查本身没有失败。
        return count($rows) === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * 找出存储值与明细对不上的账单。
     *
     * 用派生表 join 而不是相关子查询：相关子查询要对每张账单各扫一次明细表。
     * 过滤放在外层 WHERE 而不是 HAVING —— 开了 ONLY_FULL_GROUP_BY 的 MySQL 上，
     * 不带 GROUP BY 的 HAVING 会报错。
     */
    private function findDrift(): array
    {
        $rows = DB::select(
            'SELECT * FROM (
                SELECT
                    i.id,
                    i.invoice_no,
                    i.invoice_date,
                    i.payment_status,
                    COALESCE(i.total_amount, 0) AS total_amount,
                    COALESCE(i.paid_amount, 0)  AS stored_paid,
                    COALESCE(p.total, 0)        AS payments_total,
                    COALESCE(r.total, 0)        AS refunds_total
                FROM invoices i
                LEFT JOIN (
                    SELECT invoice_id, SUM(amount) AS total
                    FROM invoice_payments
                    WHERE deleted_at IS NULL
                    GROUP BY invoice_id
                ) p ON p.invoice_id = i.id
                LEFT JOIN (
                    SELECT invoice_id, SUM(refund_amount) AS total
                    FROM refunds
                    WHERE deleted_at IS NULL AND approval_status = ?
                    GROUP BY invoice_id
                ) r ON r.invoice_id = i.id
                WHERE i.deleted_at IS NULL
            ) x
            WHERE ABS(x.stored_paid - (x.payments_total - x.refunds_total)) > ?
            ORDER BY ABS(x.stored_paid - (x.payments_total - x.refunds_total)) DESC',
            ['approved', self::TOLERANCE]
        );

        return array_map(function ($row) {
            $expected = bcsub((string) $row->payments_total, (string) $row->refunds_total, 2);
            // 重算结果不会为负（syncInvoicePaidAmount 会夹到 0），体检要按同样口径
            $expected = bccomp($expected, '0', 2) >= 0 ? $expected : '0.00';

            return [
                'id'             => (int) $row->id,
                'invoice_no'     => (string) $row->invoice_no,
                'invoice_date'   => $row->invoice_date ? substr((string) $row->invoice_date, 0, 10) : '',
                'payment_status' => (string) $row->payment_status,
                'total_amount'   => $this->money($row->total_amount),
                'stored_paid'    => $this->money($row->stored_paid),
                'payments_total' => $this->money($row->payments_total),
                'refunds_total'  => $this->money($row->refunds_total),
                'expected_paid'  => $expected,
                'delta'          => bcsub($expected, $this->money($row->stored_paid), 2),
            ];
        }, $rows);
    }

    /**
     * 打印结果。delta 为负 = 重算后账单的已收金额会变少（有抹账风险，重点看这批）。
     */
    private function printTable(array $report): void
    {
        $rows = $report['rows'];

        $this->line('Database      : ' . $report['database']);
        $this->line('Invoices      : ' . $report['total_invoices']);
        $this->line('Drifting rows : ' . $report['drift_count']);

        if ($report['drift_count'] === 0) {
            $this->info('OK - stored paid_amount matches payment/refund detail on every invoice.');
            $this->line('Safe to deploy the recompute change.');

            return;
        }

        $shrinking = array_filter($rows, fn ($r) => bccomp($r['delta'], '0', 2) < 0);

        $this->line('Total |delta| : ' . $report['drift_total']);
        $this->line('Would DECREASE: ' . count($shrinking) . ' invoice(s)  <-- these lose recorded money on recompute');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $shown = $limit > 0 ? array_slice($rows, 0, $limit) : $rows;

        $this->table(
            ['ID', 'Invoice No', 'Date', 'Status', 'Total', 'Stored paid', 'Payments', 'Refunds', 'Expected', 'Delta'],
            array_map(fn ($r) => [
                $r['id'], $r['invoice_no'], $r['invoice_date'], $r['payment_status'],
                $r['total_amount'], $r['stored_paid'], $r['payments_total'],
                $r['refunds_total'], $r['expected_paid'], $r['delta'],
            ], $shown)
        );

        if ($limit > 0 && count($rows) > $limit) {
            $this->comment('... ' . (count($rows) - $limit) . ' more row(s) not shown. Use --limit=0 or --out=report.txt');
        }

        $this->newLine();
        $this->warn('Drift found. Do NOT deploy the recompute change before deciding what these rows mean:');
        $this->line('  Delta < 0 : invoice claims more paid than the detail rows show.');
        $this->line('              Either money was received but the payment row is missing (needs back-fill),');
        $this->line('              or the stored value was wrong all along (recompute fixes it).');
        $this->line('  Delta > 0 : detail rows show more than the invoice claims - recompute will raise it.');
    }

    private function writeReport(array $report): void
    {
        $path = (string) $this->option('out');

        $lines = [
            'invoices:check-paid-drift',
            'database=' . $report['database'],
            'total_invoices=' . $report['total_invoices'],
            'drift_count=' . $report['drift_count'],
            'drift_total=' . $report['drift_total'],
            '',
            implode("\t", ['id', 'invoice_no', 'invoice_date', 'payment_status',
                'total_amount', 'stored_paid', 'payments_total', 'refunds_total',
                'expected_paid', 'delta']),
        ];

        foreach ($report['rows'] as $row) {
            $lines[] = implode("\t", $row);
        }

        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);

        $this->line('Report written to ' . $path);
    }

    private function sumAbsoluteDrift(array $rows): string
    {
        $sum = '0';

        foreach ($rows as $row) {
            $delta = $row['delta'];
            $sum = bcadd($sum, bccomp($delta, '0', 2) < 0 ? bcmul($delta, '-1', 2) : $delta, 2);
        }

        return $sum;
    }

    /** 统一成两位小数的字符串，供 bcmath 使用 */
    private function money($value): string
    {
        return bcadd((string) ($value ?? 0), '0', 2);
    }
}
