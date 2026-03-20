<?php

namespace App\Http\Controllers;

use App\Exports\InventoryItemTemplateExport;
use App\Imports\InventoryItemsImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 物品批量导入控制器（AG-062 / AG-068）。
 *
 * 权限：manage-inventory
 */
class InventoryImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-inventory');
    }

    /**
     * 显示导入页面。
     */
    public function index(): \Illuminate\View\View
    {
        return view('inventory.import.index');
    }

    /**
     * 下载 Excel 导入模板。
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new InventoryItemTemplateExport(), '物品导入模板.xlsx');
    }

    /**
     * 处理文件上传并执行导入。
     *
     * AG-068: 文件大小 ≤ 10MB；行数 ≤ 5000 由 InventoryItemsImport::limit() 保证。
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',  // 10 MB
        ], [
            'file.required' => __('inventory.import_file_required'),
            'file.mimes'    => __('inventory.import_file_type'),
            'file.max'      => __('inventory.file_too_large'),
        ]);

        $file   = $request->file('file');
        $import = new InventoryItemsImport((int) Auth::id());

        try {
            Excel::import($import, $file);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status'   => true,
            'message'  => __('inventory.import_success', [
                'imported' => $import->importedCount,
                'skipped'  => $import->skippedCount,
            ]),
            'imported' => $import->importedCount,
            'skipped'  => $import->skippedCount,
            'errors'   => $import->errors,
        ]);
    }
}
