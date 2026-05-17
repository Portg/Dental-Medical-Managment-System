<?php

namespace App\Console\Commands;

use App\MedicalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UpdateMedicalServicesPriceFromXlsx extends Command
{
    protected $signature = 'medical-services:update-price-from-xlsx
                            {path : xlsx 文件路径}
                            {--sheet=Sheet2 : sheet 名称或 1-based 序号}
                            {--dry-run : 仅输出统计，不落库}
                            {--only-active : 仅更新 is_active=1 的项目}
                            {--exact : 仅使用精确名称匹配（不做规范化匹配）}
                            {--create-missing : 若未匹配到则创建新项目（仅在非 dry-run 时生效）}';

    protected $description = '根据 Excel 中的项目名称/市场价格更新 medical_services.price（按名称精确匹配）';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $sheetOpt = (string) $this->option('sheet');
        $dryRun = (bool) $this->option('dry-run');
        $onlyActive = (bool) $this->option('only-active');
        $exact = (bool) $this->option('exact');
        $createMissing = (bool) $this->option('create-missing');

        if (!is_file($path)) {
            $this->error("文件不存在：{$path}");
            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $this->resolveSheet($spreadsheet, $sheetOpt);
        if (!$sheet) {
            $this->error("找不到 sheet：{$sheetOpt}");
            return self::FAILURE;
        }

        [$nameCols, $priceCols] = $this->detectNamePriceColumns($sheet);
        if (!$nameCols || !$priceCols || count($nameCols) !== count($priceCols)) {
            $this->error('无法识别表头列（需要包含“项目名称”“市场价格”）。');
            return self::FAILURE;
        }

        $items = $this->readItems($sheet, $nameCols, $priceCols);
        if (!$items) {
            $this->warn('未读取到任何项目（可能是空表或表头行不正确）。');
            return self::SUCCESS;
        }

        $summary = [
            'rows_read' => count($items),
            'services_indexed' => 0,
            'updated' => 0,
            'created' => 0,
            'skipped_invalid_price' => 0,
            'not_found' => 0,
            'multiple_matched' => 0,
        ];
        $notFound = [];
        $multiMatched = [];
        $matched = [];

        $index = null;
        if (!$exact) {
            $index = $this->buildServiceIndex($onlyActive);
            $summary['services_indexed'] = array_sum(array_map('count', $index));
        }

        $run = function () use (&$summary, &$notFound, &$multiMatched, &$matched, $items, $dryRun, $onlyActive, $exact, $index, $createMissing) {
            foreach ($items as $name => $price) {
                if ($exact) {
                    $query = MedicalService::whereNull('deleted_at')->where('name', $name);
                    if ($onlyActive) {
                        $query->where('is_active', true);
                    }
                    $ids = $query->pluck('id')->all();
                } else {
                    $norm = $this->normalizeNameKey($name);
                    $ids = $index[$norm] ?? [];
                }

                $count = count($ids);
                if ($count === 0) {
                    if (!$dryRun && $createMissing) {
                        $svc = MedicalService::create([
                            'name' => $name,
                            'price' => $price,
                            'is_active' => true,
                            '_who_added' => 1,
                        ]);
                        $summary['created']++;
                        $matched[] = $name . ' (created)';
                        continue;
                    }
                    $summary['not_found']++;
                    if (count($notFound) < 100) {
                        $notFound[] = $name;
                    }
                    continue;
                }
                if ($count > 1) {
                    $summary['multiple_matched']++;
                    if (count($multiMatched) < 100) {
                        $multiMatched[] = $name;
                    }
                }

                if (!$dryRun) {
                    MedicalService::whereIn('id', $ids)->update(['price' => $price]);
                }
                $summary['updated']++;
                if (count($matched) < 100) {
                    $matched[] = $name;
                }
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
            Cache::forget('billing_service_category_tree');
            Cache::forget('medical_services:names');
        }

        $this->info('完成。');
        $this->line('统计：');
        foreach ($summary as $k => $v) {
            $this->line("- {$k}: {$v}");
        }

        if ($matched) {
            $this->info('已匹配/更新（最多显示 100 条）：');
            foreach ($matched as $name) {
                $this->line("  - {$name}");
            }
        }

        if ($notFound) {
            $this->warn('未匹配（最多显示 100 条）：');
            foreach ($notFound as $name) {
                $this->line("  - {$name}");
            }
        }
        if ($multiMatched) {
            $this->warn('同名多条记录（最多显示 100 条）：');
            foreach ($multiMatched as $name) {
                $this->line("  - {$name}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveSheet($spreadsheet, string $sheetOpt): ?Worksheet
    {
        if ($sheetOpt === '') {
            return $spreadsheet->getActiveSheet();
        }
        if (ctype_digit($sheetOpt)) {
            $idx = (int) $sheetOpt;
            if ($idx <= 0) {
                return null;
            }
            return $spreadsheet->getSheet($idx - 1) ?: null;
        }
        return $spreadsheet->getSheetByName($sheetOpt) ?: null;
    }

    /**
     * 识别表头行（包含“项目名称”“市场价格”）并返回对应列号（1-based）。
     *
     * @return array{0: int[], 1: int[]}
     */
    private function detectNamePriceColumns(Worksheet $sheet): array
    {
        // 优先扫前 10 行作为表头候选（该表第 2 行是表头）
        for ($row = 1; $row <= 10; $row++) {
            $cells = $this->readRowStrings($sheet, $row, 1, 50);
            $nameCols = [];
            $priceCols = [];

            foreach ($cells as $col => $value) {
                if ($value === '项目名称') {
                    // 常见格式：项目名称、单位、市场价格
                    $nameCols[] = $col;
                }
            }

            if ($nameCols) {
                foreach ($nameCols as $nameCol) {
                    $priceCol = null;
                    for ($offset = 1; $offset <= 4; $offset++) {
                        $v = $cells[$nameCol + $offset] ?? '';
                        if ($v === '市场价格') {
                            $priceCol = $nameCol + $offset;
                            break;
                        }
                    }
                    if ($priceCol) {
                        $priceCols[] = $priceCol;
                    }
                }
            }

            if ($nameCols && $priceCols && count($nameCols) === count($priceCols)) {
                return [$nameCols, $priceCols];
            }
        }

        return [[], []];
    }

    /**
     * @param int[] $nameCols
     * @param int[] $priceCols
     * @return array<string, string> name => price(2dp)
     */
    private function readItems(Worksheet $sheet, array $nameCols, array $priceCols): array
    {
        $highestRow = (int) $sheet->getHighestRow();
        $result = [];

        // 数据通常从第 3 行开始
        for ($row = 3; $row <= $highestRow; $row++) {
            $allEmpty = true;
            foreach ($nameCols as $i => $nameCol) {
                $priceCol = $priceCols[$i] ?? null;
                if (!$priceCol) {
                    continue;
                }

                $name = $this->normalizeName((string) ($sheet->getCellByColumnAndRow($nameCol, $row)->getValue() ?? ''));
                $priceRaw = $sheet->getCellByColumnAndRow($priceCol, $row)->getCalculatedValue();

                if ($name !== '') {
                    $allEmpty = false;
                }

                if ($name === '') {
                    continue;
                }

                $price = $this->normalizePrice($priceRaw);
                if ($price === null) {
                    continue;
                }

                // 同名以最后一次出现为准（后续会在输出里统计多条匹配 DB 的情况）
                $result[$name] = $price;
            }

            // 连续空行就提前退出（避免扫到很下面）
            if ($allEmpty && $row >= 10) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return array<int, string> col(1-based) => value
     */
    private function readRowStrings(Worksheet $sheet, int $row, int $startCol, int $endCol): array
    {
        $cells = [];
        for ($col = $startCol; $col <= $endCol; $col++) {
            $v = $sheet->getCellByColumnAndRow($col, $row)->getValue();
            $cells[$col] = trim((string) ($v ?? ''));
        }
        return $cells;
    }

    private function normalizeName(string $name): string
    {
        $name = str_replace(["\xc2\xa0", '　'], ' ', $name); // nbsp + 全角空格
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        return trim($name);
    }

    /**
     * 用于“模糊但可控”的同名匹配：统一中英文括号、去掉所有空白。
     */
    private function normalizeNameKey(string $name): string
    {
        $name = $this->normalizeName($name);
        $name = str_replace(['（', '）'], ['(', ')'], $name);
        $name = preg_replace('/\s+/u', '', $name) ?? $name;
        return trim($name);
    }

    /**
     * @return array<string, int[]> normalized_name => ids
     */
    private function buildServiceIndex(bool $onlyActive): array
    {
        $query = MedicalService::query()
            ->whereNull('deleted_at')
            ->select(['id', 'name']);
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        $rows = $query->get();

        $index = [];
        foreach ($rows as $row) {
            $key = $this->normalizeNameKey((string) $row->name);
            if ($key === '') {
                continue;
            }
            $index[$key] ??= [];
            $index[$key][] = (int) $row->id;
        }

        return $index;
    }

    /**
     * @param mixed $priceRaw
     */
    private function normalizePrice($priceRaw): ?string
    {
        if ($priceRaw === null) {
            return null;
        }
        $s = trim((string) $priceRaw);
        if ($s === '') {
            return null;
        }
        $s = str_replace([',', '￥', '¥'], '', $s);
        if (!is_numeric($s)) {
            return null;
        }
        // 保持两位小数
        return number_format((float) $s, 2, '.', '');
    }
}

