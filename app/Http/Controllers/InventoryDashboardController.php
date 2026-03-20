<?php

namespace App\Http\Controllers;

use App\InventoryBatch;
use App\InventoryItem;
use App\StockIn;
use App\StockOut;
use Illuminate\Support\Facades\DB;

class InventoryDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-inventory');
    }

    public function index()
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $kpi = [
            // 今日入库笔数（已确认）
            'today_stock_in' => StockIn::where('status', StockIn::STATUS_CONFIRMED)
                ->whereDate('stock_in_date', $today)
                ->count(),

            // 本月入库金额
            'month_stock_in_amount' => StockIn::where('status', StockIn::STATUS_CONFIRMED)
                ->whereBetween('stock_in_date', [$monthStart, $today])
                ->sum('total_amount'),

            // 今日出库笔数（已确认）
            'today_stock_out' => StockOut::where('status', StockOut::STATUS_CONFIRMED)
                ->whereDate('stock_out_date', $today)
                ->count(),

            // 本月代销出库笔数
            'month_billing_stock_out' => StockOut::where('status', StockOut::STATUS_CONFIRMED)
                ->whereNotNull('invoice_id')
                ->whereBetween('stock_out_date', [$monthStart, $today])
                ->count(),

            // 低库存物品数
            'low_stock_count' => InventoryItem::lowStock()->active()->count(),

            // 临期批次数（30天内）
            'expiry_warning_count' => InventoryBatch::nearExpiry(30)->count(),
        ];

        // 低库存清单（最多10条，按缺口降序）
        $lowStockItems = InventoryItem::lowStock()
            ->active()
            ->with('category')
            ->orderByRaw('(stock_warning_level - current_stock) DESC')
            ->limit(10)
            ->get();

        // 临期批次清单（最多10条，按有效期升序）
        $expiryBatches = InventoryBatch::with('inventoryItem')
            ->nearExpiry(30)
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        return view('inventory.dashboard', compact('kpi', 'lowStockItems', 'expiryBatches'));
    }
}
