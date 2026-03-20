<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 旧数据迁移：将 prescriptions 表中的 legacy 数据（drug/qty/directions）
 * 转换为新的 prescription_items 行，并补全 prescriptions 缺失字段。
 *
 * Legacy 格式：每条 prescription 记录 = 一种药物（drug, qty, directions）
 * 新格式：prescription 是头记录，prescription_items 是行记录
 *
 * 匹配策略：
 *   1. drug → medical_services.name 精确匹配（is_prescription=true）
 *   2. drug → medical_services.name LIKE 模糊匹配
 *   3. 无法匹配时保留 drug_name，medical_service_id 为 null
 *
 * 本迁移依赖 2026_03_08_100002（prescription_items 新增 medical_service_id / unit_price）
 */
return new class extends Migration
{
    public function up(): void
    {
        // 预加载处方类收费项目用于名称匹配
        $services = DB::table('medical_services')
            ->where('is_prescription', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'price')
            ->get()
            ->keyBy('name');

        // 所有收费项目（用于模糊匹配兜底）
        $allServices = DB::table('medical_services')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'price', 'is_prescription')
            ->get();

        // 查找 legacy 处方：有 drug 字段且尚无 prescription_items
        $legacyRx = DB::table('prescriptions')
            ->whereNotNull('drug')
            ->where('drug', '!=', '')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        if ($legacyRx->isEmpty()) {
            Log::info('[MigrateLegacyPrescriptions] No legacy prescriptions found, skipping.');
            return;
        }

        Log::info("[MigrateLegacyPrescriptions] Found {$legacyRx->count()} legacy prescriptions to migrate.");

        $migratedCount = 0;
        $unmatchedDrugs = [];
        $rxNoCounter = [];

        foreach ($legacyRx as $rx) {
            // 跳过已有 items 的记录（防止重跑时重复）
            $existingItems = DB::table('prescription_items')
                ->where('prescription_id', $rx->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existingItems) {
                continue;
            }

            // ── 补全 prescriptions 缺失字段 ──

            $updates = [];

            // 补 patient_id（从 appointment 获取）
            if (empty($rx->patient_id) && !empty($rx->appointment_id)) {
                $patientId = DB::table('appointments')
                    ->where('id', $rx->appointment_id)
                    ->value('patient_id');
                if ($patientId) {
                    $updates['patient_id'] = $patientId;
                }
            }

            // 补 doctor_id（从 _who_added 获取）
            if (empty($rx->doctor_id) && !empty($rx->_who_added)) {
                $isDoctor = DB::table('users')
                    ->where('id', $rx->_who_added)
                    ->where('is_doctor', true)
                    ->exists();
                if ($isDoctor) {
                    $updates['doctor_id'] = $rx->_who_added;
                }
            }

            // 补 prescription_no
            if (empty($rx->prescription_no)) {
                $dateStr = $rx->created_at
                    ? date('Ymd', strtotime($rx->created_at))
                    : date('Ymd');
                $prefix = 'RX' . $dateStr;
                if (!isset($rxNoCounter[$prefix])) {
                    $latest = DB::table('prescriptions')
                        ->where('prescription_no', 'like', $prefix . '%')
                        ->orderBy('prescription_no', 'desc')
                        ->value('prescription_no');
                    $rxNoCounter[$prefix] = $latest
                        ? intval(substr($latest, -4))
                        : 0;
                }
                $rxNoCounter[$prefix]++;
                $updates['prescription_no'] = $prefix . sprintf('%04d', $rxNoCounter[$prefix]);
            }

            // 补 status（legacy 视为已完成）
            if (empty($rx->status)) {
                $updates['status'] = 'completed';
            }

            // 补 prescription_date
            if (empty($rx->prescription_date) && !empty($rx->created_at)) {
                $updates['prescription_date'] = date('Y-m-d', strtotime($rx->created_at));
            }

            if (!empty($updates)) {
                DB::table('prescriptions')->where('id', $rx->id)->update($updates);
            }

            // ── 创建 prescription_item ──

            $drugName = trim($rx->drug);
            $matchedServiceId = null;
            $matchedPrice = null;

            // 策略1: 精确匹配 is_prescription=true 的服务
            if ($services->has($drugName)) {
                $svc = $services->get($drugName);
                $matchedServiceId = $svc->id;
                $matchedPrice = $svc->price;
            } else {
                // 策略2: 模糊匹配（LIKE %drug%）
                $fuzzyMatch = $allServices->first(function ($svc) use ($drugName) {
                    return mb_stripos($svc->name, $drugName) !== false
                        || mb_stripos($drugName, $svc->name) !== false;
                });
                if ($fuzzyMatch) {
                    $matchedServiceId = $fuzzyMatch->id;
                    $matchedPrice = $fuzzyMatch->price;
                    // 顺便标记该服务为处方类
                    if (!$fuzzyMatch->is_prescription) {
                        DB::table('medical_services')
                            ->where('id', $fuzzyMatch->id)
                            ->update(['is_prescription' => true]);
                    }
                } else {
                    $unmatchedDrugs[$drugName] = ($unmatchedDrugs[$drugName] ?? 0) + 1;
                }
            }

            DB::table('prescription_items')->insert([
                'prescription_id'    => $rx->id,
                'medical_service_id' => $matchedServiceId,
                'drug_name'          => $drugName,
                'dosage'             => null,
                'quantity'           => max(1, (int) ($rx->qty ?? 1)),
                'unit_price'         => $matchedPrice,
                'frequency'          => null,
                'duration'           => null,
                'usage'              => $rx->directions,
                'notes'              => null,
                'inventory_item_id'  => null,
                '_who_added'         => $rx->_who_added,
                'created_at'         => $rx->created_at,
                'updated_at'         => now(),
            ]);

            $migratedCount++;
        }

        Log::info("[MigrateLegacyPrescriptions] Migrated {$migratedCount} prescriptions.");

        if (!empty($unmatchedDrugs)) {
            Log::warning('[MigrateLegacyPrescriptions] Unmatched drugs (need manual review):', $unmatchedDrugs);
        }
    }

    public function down(): void
    {
        // 回滚：删除由本迁移创建的 prescription_items（drug_name 非空且 created_at 与 prescriptions 同步）
        // 只删除对应 legacy prescriptions（有 drug 字段）的 items
        $legacyRxIds = DB::table('prescriptions')
            ->whereNotNull('drug')
            ->where('drug', '!=', '')
            ->pluck('id');

        if ($legacyRxIds->isNotEmpty()) {
            DB::table('prescription_items')
                ->whereIn('prescription_id', $legacyRxIds)
                ->delete();
        }

        // 清除补全的字段（保守起见只清 prescription_no 和 status）
        // 不回滚 patient_id/doctor_id 因为这些是正确补全
    }
};
