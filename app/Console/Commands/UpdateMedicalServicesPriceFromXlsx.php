<?php

namespace App\Console\Commands;

use App\MedicalService;
use App\ServiceCategory;
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
                            {--create-missing : 若未匹配到则创建新项目（仅在非 dry-run 时生效）}
                            {--fill-missing-fields : 对已存在项目补齐 unit/category_id（仅当原字段为空时）}
                            {--sync-fields : 强制同步 unit/category_id（覆盖已有值）}
                            {--report : 输出分类/单位对照报告}
                            {--report-limit=50 : 报告最多输出多少条}
                            {--who-added=1 : 创建新项目时写入 _who_added（用户ID）}';

    protected $description = '根据 Excel 中的项目名称/市场价格更新 medical_services.price（按名称精确匹配）';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $sheetOpt = (string) $this->option('sheet');
        $dryRun = (bool) $this->option('dry-run');
        $onlyActive = (bool) $this->option('only-active');
        $exact = (bool) $this->option('exact');
        $createMissing = (bool) $this->option('create-missing');
        $fillMissingFields = (bool) $this->option('fill-missing-fields');
        $syncFields = (bool) $this->option('sync-fields');
        $report = (bool) $this->option('report');
        $reportLimit = (int) $this->option('report-limit');
        $whoAdded = (int) $this->option('who-added');

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

        [$blocks, $headerRow] = $this->detectBlocks($sheet);
        if (!$blocks) {
            $this->error('无法识别表头列（需要包含“项目名称”“市场价格”）。');
            return self::FAILURE;
        }

        $items = $this->readItems($sheet, $blocks, $headerRow);
        if (!$items) {
            $this->warn('未读取到任何项目（可能是空表或表头行不正确）。');
            return self::SUCCESS;
        }

        $summary = [
            'rows_read' => count($items),
            'services_indexed' => 0,
            'updated' => 0,
            'created' => 0,
            'fields_filled' => 0,
            'fields_synced' => 0,
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

        $run = function () use (
            &$summary,
            &$notFound,
            &$multiMatched,
            &$matched,
            $items,
            $dryRun,
            $onlyActive,
            $exact,
            $index,
            $createMissing,
            $fillMissingFields,
            $syncFields,
            $report,
            $reportLimit,
            $whoAdded
        ) {
            $reportRows = [];
            $reportMismatch = 0;

            foreach ($items as $name => $payload) {
                $price = $payload['price'];
                $unit = $payload['unit'] ?? null;
                $categoryName = $payload['category'] ?? null;

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
                        $categoryId = null;
                        if ($categoryName) {
                            $cat = ServiceCategory::firstOrCreate(
                                ['name' => $categoryName],
                                ['sort_order' => 0, 'is_active' => true, '_who_added' => $whoAdded]
                            );
                            $categoryId = (int) $cat->id;
                        }

                        MedicalService::create([
                            'name' => $name,
                            'unit' => $unit,
                            'price' => $price,
                            'category' => $categoryName,
                            'category_id' => $categoryId,
                            'is_active' => true,
                            '_who_added' => $whoAdded,
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
                    // 先统一更新价格
                    MedicalService::whereIn('id', $ids)->update(['price' => $price]);

                    // 再补齐缺失字段（仅当目标字段为空/NULL）
                    if (($fillMissingFields || $syncFields) && ($unit || $categoryName)) {
                        $fill = [];
                        if ($unit) {
                            $fill['unit'] = $unit;
                        }

                        if ($categoryName) {
                            $cat = ServiceCategory::firstOrCreate(
                                ['name' => $categoryName],
                                ['sort_order' => 0, 'is_active' => true, '_who_added' => $whoAdded]
                            );
                            $fill['category'] = $categoryName;
                            $fill['category_id'] = (int) $cat->id;
                        }

                        if ($fill) {
                            if ($syncFields) {
                                $summary['fields_synced'] += MedicalService::whereIn('id', $ids)->update($fill);
                            } else {
                                $affected = MedicalService::whereIn('id', $ids)->where(function ($q) use ($fill) {
                                    foreach (array_keys($fill) as $field) {
                                        $q->orWhereNull($field)->orWhere($field, '');
                                    }
                                })->update($fill);
                                $summary['fields_filled'] += $affected;
                            }
                        }
                    }
                }
                $summary['updated']++;
                if (count($matched) < 100) {
                    $matched[] = $name;
                }

                if ($report && count($reportRows) < max(0, $reportLimit)) {
                    $id = (int) $ids[0];
                    $svc = MedicalService::where('id', $id)->first(['id', 'name', 'unit', 'category', 'category_id']);
                    $dbCategoryId = $svc?->category_id;
                    $dbCategoryName = $dbCategoryId ? (ServiceCategory::where('id', $dbCategoryId)->value('name') ?? null) : null;
                    $dbCategoryLegacy = $svc?->category;

                    $expectedCategoryId = null;
                    if ($categoryName) {
                        $expectedCategoryId = (int) ServiceCategory::firstOrCreate(
                            ['name' => $categoryName],
                            ['sort_order' => 0, 'is_active' => true, '_who_added' => $whoAdded]
                        )->id;
                    }

                    $ok = ($expectedCategoryId === null || (int) $dbCategoryId === (int) $expectedCategoryId);
                    if (!$ok) {
                        $reportMismatch++;
                    }

                    $reportRows[] = [
                        'name' => $name,
                        'excel_category' => $categoryName,
                        'excel_unit' => $unit,
                        'expected_category_id' => $expectedCategoryId,
                        'db_category_id' => $dbCategoryId,
                        'db_category_name' => $dbCategoryName,
                        'db_category_legacy' => $dbCategoryLegacy,
                        'db_unit' => $svc?->unit,
                        'ok' => $ok ? 'Y' : 'N',
                    ];
                }
            }

            if ($report) {
                $this->line('');
                $this->info("报告（最多 {$reportLimit} 条；category_id 不一致计数={$reportMismatch}）：");
                foreach ($reportRows as $r) {
                    $this->line(
                        "- {$r['ok']} {$r['name']} | Excel: [{$r['excel_category']}] unit={$r['excel_unit']} -> expect_cat_id={$r['expected_category_id']} | DB: cat_id={$r['db_category_id']} cat_name={$r['db_category_name']} legacy_cat={$r['db_category_legacy']} unit={$r['db_unit']}"
                    );
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
     * 识别表头所在行，并按“块”返回列映射：每个块 = {categoryCol, nameCol, unitCol, priceCol}
     *
     * @return array{0: array<int, array{categoryCol:int|null,nameCol:int,unitCol:int|null,priceCol:int}>, 1: int}
     */
    private function detectBlocks(Worksheet $sheet): array
    {
        for ($row = 1; $row <= 10; $row++) {
            $cells = $this->readRowStrings($sheet, $row, 1, 50);

            $blocks = [];
            foreach ($cells as $col => $value) {
                if ($value !== '项目名称') {
                    continue;
                }
                $unitCol = null;
                $priceCol = null;
                for ($offset = 1; $offset <= 4; $offset++) {
                    $v = $cells[$col + $offset] ?? '';
                    if ($v === '单位') {
                        $unitCol = $col + $offset;
                    }
                    if ($v === '市场价格') {
                        $priceCol = $col + $offset;
                    }
                }
                if ($priceCol) {
                    // 分类列通常在该块的 nameCol 前 1 列
                    $categoryCol = ($col - 1) >= 1 ? ($col - 1) : null;
                    $blocks[] = [
                        'categoryCol' => $categoryCol,
                        'nameCol' => $col,
                        'unitCol' => $unitCol,
                        'priceCol' => $priceCol,
                    ];
                }
            }

            if ($blocks) {
                return [$blocks, $row];
            }
        }

        return [[], 0];
    }

    /**
     * @param array<int, array{categoryCol:int|null,nameCol:int,unitCol:int|null,priceCol:int}> $blocks
     * @return array<string, array{price: string, unit?: string|null, category?: string|null}>
     */
    private function readItems(Worksheet $sheet, array $blocks, int $headerRow): array
    {
        $highestRow = (int) $sheet->getHighestRow();
        $result = [];
        $lastCategoryByBlock = [];

        $startRow = max($headerRow + 1, 2);
        for ($row = $startRow; $row <= $highestRow; $row++) {
            $allEmpty = true;
            foreach ($blocks as $blockIndex => $block) {
                $nameCol = $block['nameCol'];
                $unitCol = $block['unitCol'];
                $priceCol = $block['priceCol'];
                $categoryCol = $block['categoryCol'];

                $name = $this->normalizeName((string) ($sheet->getCellByColumnAndRow($nameCol, $row)->getValue() ?? ''));
                $priceRaw = $sheet->getCellByColumnAndRow($priceCol, $row)->getCalculatedValue();
                $unit = $unitCol ? $this->normalizeName((string) ($sheet->getCellByColumnAndRow($unitCol, $row)->getValue() ?? '')) : null;
                $category = $categoryCol ? $this->normalizeName((string) ($sheet->getCellByColumnAndRow($categoryCol, $row)->getValue() ?? '')) : null;
                if ($category === '' || $category === null) {
                    $category = $lastCategoryByBlock[$blockIndex] ?? null;
                } else {
                    $lastCategoryByBlock[$blockIndex] = $category;
                }

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

                $result[$name] = [
                    'price' => $price,
                    'unit' => $unit ?: null,
                    'category' => $category ?: null,
                ];
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

