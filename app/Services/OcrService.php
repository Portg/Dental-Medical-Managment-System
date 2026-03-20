<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OcrService
{
    /**
     * Recognize text from an image.
     *
     * Tries the persistent OCR HTTP server first (fast: model already loaded).
     * Falls back to spawning a Python subprocess if the server is unavailable.
     *
     * @param string $imagePath Absolute path to the image file
     * @return array{raw_text: string, lines: array, confidence: float}
     * @throws \RuntimeException
     */
    public function recognize(string $imagePath): array
    {
        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        // Try persistent HTTP server first (much faster — no model reload)
        $serverUrl = config('services.ocr.server_url');
        if ($serverUrl) {
            try {
                return $this->recognizeViaServer($imagePath, $serverUrl);
            } catch (\Exception $e) {
                // Server not running — fall through to subprocess
                \Log::debug('OCR server unavailable, falling back to subprocess: ' . $e->getMessage());
            }
        }

        return $this->recognizeViaProcess($imagePath);
    }

    /**
     * Call the persistent OCR HTTP server.
     */
    private function recognizeViaServer(string $imagePath, string $serverUrl): array
    {
        $timeout = (int) config('services.ocr.timeout', 60);

        $fp = fopen($imagePath, 'r');
        if ($fp === false) {
            throw new \RuntimeException("Cannot read image file: {$imagePath}");
        }

        $response = Http::connectTimeout(3)
            ->timeout($timeout)
            ->attach('image', $fp, basename($imagePath))
            ->post("{$serverUrl}/recognize");

        if (!$response->successful()) {
            throw new \RuntimeException('OCR server error: ' . $response->body());
        }

        $result = $response->json();

        if (isset($result['error'])) {
            throw new \RuntimeException('OCR server error: ' . $result['error']);
        }

        return [
            'raw_text'   => $result['raw_text'] ?? '',
            'lines'      => $result['lines'] ?? [],
            'confidence' => $result['avg_confidence'] ?? 0,
        ];
    }

    /**
     * Fall back: spawn Python subprocess (slower — reloads model each time).
     */
    private function recognizeViaProcess(string $imagePath): array
    {
        $pythonPath = config('services.ocr.python_path', 'python3');
        $scriptPath = config('services.ocr.script_path', base_path('scripts/ocr_service.py'));
        $timeout = (int) config('services.ocr.timeout', 60);

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("OCR script not found: {$scriptPath}");
        }

        $process = new Process([$pythonPath, $scriptPath, $imagePath]);
        $process->setTimeout($timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $stderr = $process->getErrorOutput();
            throw new \RuntimeException("OCR recognition failed: {$stderr}");
        }

        $output = $process->getOutput();
        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('OCR output is not valid JSON: ' . json_last_error_msg());
        }

        return [
            'raw_text'   => $result['raw_text'] ?? '',
            'lines'      => $result['lines'] ?? [],
            'confidence' => $result['avg_confidence'] ?? 0,
        ];
    }

    /**
     * Parse OCR raw text into structured patient and case fields.
     *
     * @param string $rawText OCR recognized text
     * @return array{patient: array, case: array}
     */
    public function parseMedicalRecord(string $rawText): array
    {
        $lines = preg_split('/\r?\n/', $rawText);
        $lines = array_filter($lines, fn($line) => trim($line) !== '');
        $lines = array_values($lines);

        // Patient field keywords
        $patientKeywords = [
            'full_name'   => ['姓名', '患者姓名', '病人姓名'],
            'gender'      => ['性别'],
            'age'         => ['年龄'],
            'phone_no'    => ['电话', '联系电话', '手机', '手机号', '电话号码'],
            'dob'         => ['出生日期', '出生年月', '生日'],
            'address'     => ['地址', '住址', '居住地', '工作单位或住址'],
            'nin'         => ['身份证', '身份证号'],
            'allergy'     => ['过敏', '药物过敏', '药物过敏史', '过敏史'],
            'blood_type'  => ['血型'],
        ];

        // Case field keywords (SOAP)
        // 治疗意见 = alias for treatment; 既往史 grouped under history
        $caseKeywords = [
            'chief_complaint'            => ['主诉'],
            'history_of_present_illness' => ['现病史', '病史', '既往史'],
            'examination'                => ['检查', '口腔检查', '检查所见', '查体'],
            'auxiliary_examination'      => ['辅助检查', '辅检'],
            'diagnosis'                  => ['诊断', '初步诊断'],
            'treatment'                  => ['治疗', '治疗计划', '处置', '治疗方案', '治疗意见'],
            'medical_orders'             => ['医嘱', '处方', '用药'],
        ];

        $patient = [];
        $case    = [];

        // Build flat keyword maps for quick lookup
        $patientKwMap = [];
        foreach ($patientKeywords as $field => $kws) {
            foreach ($kws as $kw) {
                $patientKwMap[$kw] = $field;
            }
        }
        $caseKwMap = [];
        foreach ($caseKeywords as $field => $kws) {
            foreach ($kws as $kw) {
                $caseKwMap[$kw] = $field;
            }
        }
        $allKwMap = array_merge($caseKwMap, $patientKwMap);

        // ── Pass 1: Inline "关键词：值" on the same line ────────────────────
        foreach ($lines as $line) {
            $this->extractInlineFields($line, $patientKeywords, $patient);
        }

        // ── Pass 2: Standalone label → next non-label, non-checkbox line ────
        // Handles printed forms where label and value occupy separate OCR lines.
        // e.g.  联系电话\n15233162997
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            if (!isset($patientKwMap[$trimmed])) {
                continue;
            }
            $field = $patientKwMap[$trimmed];
            if (!empty($patient[$field])) {
                continue;
            }
            for ($j = $idx + 1; $j < count($lines); $j++) {
                $next = trim($lines[$j]);
                if ($next === '') {
                    continue;
                }
                // Stop if we hit a line that starts with any known keyword
                // (handles both bare labels and "关键词：..." lines)
                $hitsKeyword = false;
                foreach (array_keys($allKwMap) as $kw) {
                    if (mb_strpos($next, $kw) === 0) {
                        $hitsKeyword = true;
                        break;
                    }
                }
                if ($hitsKeyword) {
                    break;
                }
                // Skip printed checkbox options like "口未婚"
                if (mb_substr($next, 0, 1) === '口' && mb_strlen($next) <= 5) {
                    continue;
                }
                $patient[$field] = $next;
                break;
            }
        }

        // ── Pass 3: Name before label ────────────────────────────────────────
        // Some forms render the filled-in value above/left of the label text,
        // so OCR produces: "曹铭熙\n姓名" instead of "姓名：曹铭熙".
        if (empty($patient['full_name'])) {
            foreach ($lines as $idx => $line) {
                if (trim($line) === '姓名' && $idx > 0) {
                    $prev = trim($lines[$idx - 1]);
                    // Accept 2–4 Chinese characters that are not a known keyword
                    if (preg_match('/^[\x{4e00}-\x{9fa5}]{2,4}$/u', $prev)
                        && !isset($allKwMap[$prev])) {
                        $patient['full_name'] = $prev;
                        break;
                    }
                }
            }
        }

        // ── Pass 4: Phone number regex fallback ──────────────────────────────
        if (empty($patient['phone_no'])) {
            if (preg_match('/1[3-9]\d{9}/', $rawText, $m)) {
                $patient['phone_no'] = $m[0];
            }
        }

        // ── Pass 5: Age regex fallback ────────────────────────────────────────
        // OCR often misreads '1' as 'l' (lowercase L), e.g. "l0岁" for "10岁".
        if (empty($patient['age'])) {
            $ageText = preg_replace('/[lL](?=\d)/', '1', $rawText);
            if (preg_match('/(\d{1,3})岁/u', $ageText, $m)) {
                $patient['age'] = $m[1];
            }
        }

        // ── Pass 6: Case fields (inline colon + multi-line content) ──────────
        // Sort by keyword length descending so "治疗意见" matches before "治疗"
        uksort($allKwMap, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

        $sections = [];
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            foreach ($allKwMap as $kw => $field) {
                if (mb_strpos($trimmed, $kw) === 0) {
                    $sections[] = [
                        'line_idx' => $idx,
                        'field'    => $field,
                        'value'    => $this->extractValueAfterKeyword($trimmed, $kw),
                        'is_case'  => isset($caseKwMap[$kw]),
                    ];
                    break;
                }
            }
        }

        foreach ($sections as $i => $section) {
            if (!$section['is_case']) {
                continue;
            }
            $field    = $section['field'];
            $startIdx = $section['line_idx'];
            $endIdx   = isset($sections[$i + 1]) ? $sections[$i + 1]['line_idx'] : count($lines);

            $parts = [];
            if (!empty($section['value'])) {
                $parts[] = $section['value'];
            }
            for ($j = $startIdx + 1; $j < $endIdx; $j++) {
                $t = trim($lines[$j]);
                if ($t !== '') {
                    $parts[] = $t;
                }
            }

            $content = implode("\n", $parts);
            if (!empty($content) && empty($case[$field])) {
                $case[$field] = $content;
            }
        }

        $patient = $this->normalizePatientFields($patient);

        // ── Pass 7: Extract case_date (就诊日期) ─────────────────────────────
        $caseDate = null;
        // Match "YYYY年MM月DD日"
        if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/u', $rawText, $dm)) {
            $caseDate = sprintf('%04d-%02d-%02d', (int) $dm[1], (int) $dm[2], (int) $dm[3]);
        }
        if ($caseDate) {
            $case['case_date'] = $caseDate;
        }

        // ── Pass 8: Extract tooth positions → FDI notation ───────────────────
        $allCaseText = implode("\n", array_filter([
            $case['chief_complaint'] ?? '',
            $case['examination'] ?? '',
            $case['diagnosis'] ?? '',
            $case['treatment'] ?? '',
        ]));
        $teeth = $this->extractTeeth($allCaseText);

        return [
            'patient' => $patient,
            'case'    => $case,
            'teeth'   => $teeth,
        ];
    }

    /**
     * Extract inline key-value pairs from a single line.
     * Handles formats like "姓名：张三  性别：男  年龄：35岁"
     */
    private function extractInlineFields(string $line, array $keywords, array &$result): void
    {
        foreach ($keywords as $field => $kwList) {
            if (!empty($result[$field])) {
                continue;
            }
            foreach ($kwList as $kw) {
                // Match "关键词：值" or "关键词:值" patterns
                $pattern = '/' . preg_quote($kw, '/') . '[：:]\s*([^\s：:]+(?:\s+[^\s：:]+)*?)(?=\s+\S+[：:]|\s*$)/u';
                if (preg_match($pattern, $line, $matches)) {
                    $value = trim($matches[1]);
                    if (!empty($value)) {
                        $result[$field] = $value;
                    }
                    break;
                }
            }
        }
    }

    /**
     * Extract the value after a keyword and its separator (：or :).
     */
    private function extractValueAfterKeyword(string $text, string $keyword): string
    {
        $pos = mb_strpos($text, $keyword);
        if ($pos === false) {
            return '';
        }
        $after = mb_substr($text, $pos + mb_strlen($keyword));
        $after = preg_replace('/^[：:\s]+/', '', $after);
        return trim($after);
    }

    /**
     * Extract tooth positions from clinical text and convert to FDI notation.
     *
     * Supported input formats:
     *   1. Chinese position + Arabic:  右上5, 左下6乳牙
     *   2. Chinese position + Roman:   右上Ⅴ (always deciduous)
     *   3. Direct FDI with context:    牙位15, FDI 55
     *
     * @return string[] FDI tooth numbers, e.g. ["65", "15"]
     */
    public function extractTeeth(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        $teeth = [];

        // Quadrant map: Chinese → [permanent, deciduous]
        $quadrantMap = [
            '右上' => [1, 5],
            '左上' => [2, 6],
            '左下' => [3, 7],
            '右下' => [4, 8],
        ];

        $romanToArabic = [
            'Ⅰ' => 1, 'Ⅱ' => 2, 'Ⅲ' => 3, 'Ⅳ' => 4, 'Ⅴ' => 5,
        ];

        // Pattern 1: Chinese position + Arabic digit
        // e.g. "左上5乳牙滞留" → deciduous upper-left 5 = FDI 65
        // e.g. "右上6" → permanent upper-right 6 = FDI 16
        foreach ($quadrantMap as $pos => [$permQ, $decidQ]) {
            $pattern = '/' . preg_quote($pos, '/') . '(\d)(?:[号])?(.{0,6})/u';
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $num = (int) $m[1];
                    if ($num < 1 || $num > 8) {
                        continue;
                    }
                    $after = $m[2] ?? '';

                    // Deciduous if: num ≤ 5 AND "乳" appears in trailing context
                    $isDeciduous = ($num <= 5) && (mb_strpos($after, '乳') !== false);

                    $quadrant = $isDeciduous ? $decidQ : $permQ;
                    $teeth[] = (string) ($quadrant * 10 + $num);
                }
            }
        }

        // Pattern 2: Chinese position + Roman numeral (always deciduous)
        // e.g. "右上Ⅴ" → deciduous upper-right 5 = FDI 55
        foreach ($quadrantMap as $pos => [$permQ, $decidQ]) {
            foreach ($romanToArabic as $roman => $num) {
                if (mb_strpos($text, $pos . $roman) !== false) {
                    $teeth[] = (string) ($decidQ * 10 + $num);
                }
            }
        }

        // Pattern 3: Direct FDI with dental context keyword
        // e.g. "牙位15", "FDI 55", "第15牙"
        // Require context prefix to avoid false positives with phone numbers / IDs
        if (preg_match_all('/(?:牙位|FDI|第)\s*(\d{2})(?!\d)/iu', $text, $fdiMatches)) {
            foreach ($fdiMatches[1] as $fdiStr) {
                $fdi = (int) $fdiStr;
                if ($this->isValidFdi($fdi)) {
                    $teeth[] = (string) $fdi;
                }
            }
        }

        return array_values(array_unique($teeth));
    }

    /**
     * Check if a number is a valid FDI tooth notation.
     * Permanent: quadrant 1-4, tooth 1-8; Deciduous: quadrant 5-8, tooth 1-5.
     */
    private function isValidFdi(int $fdi): bool
    {
        $quadrant = intdiv($fdi, 10);
        $tooth    = $fdi % 10;

        if ($quadrant >= 1 && $quadrant <= 4) {
            return $tooth >= 1 && $tooth <= 8;
        }
        if ($quadrant >= 5 && $quadrant <= 8) {
            return $tooth >= 1 && $tooth <= 5;
        }
        return false;
    }

    /**
     * Normalize patient field values (gender, blood type, age, etc.)
     */
    private function normalizePatientFields(array $patient): array
    {
        // Gender mapping
        if (!empty($patient['gender'])) {
            $genderMap = [
                '男' => 'Male', '女' => 'Female',
                '男性' => 'Male', '女性' => 'Female',
                'M' => 'Male', 'F' => 'Female',
            ];
            $patient['gender'] = $genderMap[$patient['gender']] ?? $patient['gender'];
        }

        // Blood type mapping
        if (!empty($patient['blood_type'])) {
            $btRaw = $patient['blood_type'];
            $bloodTypeMap = [
                'A型'  => 'A', 'B型'  => 'B', 'AB型' => 'AB', 'O型'  => 'O',
                'A'    => 'A', 'B'    => 'B', 'AB'   => 'AB', 'O'    => 'O',
            ];
            // Check for Rh negative
            $isRhNeg = mb_strpos($btRaw, 'Rh阴') !== false || mb_strpos($btRaw, 'Rh-') !== false;
            foreach ($bloodTypeMap as $label => $val) {
                if (mb_strpos($btRaw, $label) !== false || $btRaw === $label) {
                    $patient['blood_type'] = $isRhNeg ? $val . '_Rh_negative' : $val;
                    break;
                }
            }
        }

        // Age — strip trailing "岁"
        if (!empty($patient['age'])) {
            $patient['age'] = preg_replace('/岁.*$/', '', $patient['age']);
        }

        // Allergy → drug_allergies_other
        if (!empty($patient['allergy'])) {
            $patient['drug_allergies_other'] = $patient['allergy'];
            unset($patient['allergy']);
        }

        return $patient;
    }
}
