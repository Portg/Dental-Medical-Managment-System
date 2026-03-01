<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OcrService
{
    /**
     * Call PaddleOCR Python script to recognize text from an image.
     *
     * @param string $imagePath Absolute path to the image file
     * @return array{raw_text: string, lines: array, confidence: float}
     * @throws \RuntimeException
     */
    public function recognize(string $imagePath): array
    {
        $pythonPath = config('services.ocr.python_path', 'python3');
        $scriptPath = config('services.ocr.script_path', base_path('scripts/ocr_service.py'));
        $timeout = (int) config('services.ocr.timeout', 60);

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("OCR script not found: {$scriptPath}");
        }

        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
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
            'address'     => ['地址', '住址', '居住地'],
            'nin'         => ['身份证', '身份证号'],
            'allergy'     => ['过敏', '药物过敏', '过敏史'],
            'blood_type'  => ['血型'],
        ];

        // Case field keywords (SOAP)
        $caseKeywords = [
            'chief_complaint'            => ['主诉'],
            'history_of_present_illness' => ['现病史', '病史'],
            'examination'                => ['检查', '口腔检查', '检查所见', '查体'],
            'auxiliary_examination'       => ['辅助检查', '辅检'],
            'diagnosis'                  => ['诊断', '初步诊断'],
            'treatment'                  => ['治疗', '治疗计划', '处置', '治疗方案'],
            'medical_orders'             => ['医嘱', '处方', '用药'],
        ];

        $patient = [];
        $case = [];

        // First pass: extract inline key-value pairs (e.g. "姓名：张三  性别：男  年龄：35")
        foreach ($lines as $line) {
            $this->extractInlineFields($line, $patientKeywords, $patient);
        }

        // Second pass: extract multi-line case fields
        $allCaseKeywordsList = [];
        foreach ($caseKeywords as $field => $keywords) {
            foreach ($keywords as $kw) {
                $allCaseKeywordsList[$kw] = $field;
            }
        }

        // Also add patient multi-line keywords that might span lines
        $allPatientKeywordsList = [];
        foreach ($patientKeywords as $field => $keywords) {
            foreach ($keywords as $kw) {
                $allPatientKeywordsList[$kw] = $field;
            }
        }

        $allKeywords = array_merge($allCaseKeywordsList, $allPatientKeywordsList);

        // Find keyword positions
        $sections = [];
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            foreach ($allKeywords as $kw => $field) {
                if (mb_strpos($trimmed, $kw) === 0) {
                    // Extract value after the keyword and separator
                    $value = $this->extractValueAfterKeyword($trimmed, $kw);
                    $isCaseField = isset($allCaseKeywordsList[$kw]);
                    $sections[] = [
                        'line_idx' => $idx,
                        'field'    => $field,
                        'keyword'  => $kw,
                        'value'    => $value,
                        'is_case'  => $isCaseField,
                    ];
                    break;
                }
            }
        }

        // For case fields, gather multi-line content until next keyword section
        foreach ($sections as $i => $section) {
            if (!$section['is_case']) {
                continue;
            }
            $field = $section['field'];
            $startIdx = $section['line_idx'];
            $endIdx = isset($sections[$i + 1]) ? $sections[$i + 1]['line_idx'] : count($lines);

            $contentParts = [];
            if (!empty($section['value'])) {
                $contentParts[] = $section['value'];
            }
            for ($j = $startIdx + 1; $j < $endIdx; $j++) {
                $contentParts[] = trim($lines[$j]);
            }

            $content = implode("\n", array_filter($contentParts));
            if (!empty($content) && empty($case[$field])) {
                $case[$field] = $content;
            }
        }

        // Post-process patient fields
        $patient = $this->normalizePatientFields($patient);

        return [
            'patient' => $patient,
            'case'    => $case,
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
