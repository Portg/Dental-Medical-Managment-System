<?php

namespace App\Http\Controllers;

use App\InventoryBatch;
use App\InventoryCategory;
use App\InventoryItem;
use App\StockIn;
use App\StockInItem;
use App\StockOut;
use App\StockOutItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class InventoryQueryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasPermission('manage-inventory') &&
                !auth()->user()->hasPermission('operate-inventory')) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Show the inventory query page (Tab container, no data pre-loaded).
     */
    public function index()
    {
        return view('inventory.query.index');
    }

    /**
     * DataTable: 库存汇总 (inventory_items + category)
     */
    public function stockSummary(Request $request)
    {
        $canSeeCost = auth()->user()->hasPermission('manage-inventory');

        $query = InventoryItem::with('category')
            ->whereNull('inventory_items.deleted_at');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category_name', function ($row) {
                return $row->category ? $row->category->name : '-';
            })
            ->addColumn('item_name_display', function ($row) {
                return $row->name;
            })
            ->addColumn('stock_level', function ($row) {
                $stock = $row->current_stock ?? 0;
                $warning = $row->stock_warning_level ?? 0;
                return $stock . ' / ' . $warning;
            })
            ->addColumn('average_cost_display', function ($row) use ($canSeeCost) {
                if (!$canSeeCost) {
                    return '-';
                }
                return number_format((float) $row->average_cost, 2);
            })
            ->setRowClass(function ($row) {
                $stock = (float) ($row->current_stock ?? 0);
                $warning = (int) ($row->stock_warning_level ?? 0);
                if ($stock <= $warning) {
                    return 'row-low-stock';
                }
                return '';
            })
            ->rawColumns([])
            ->make(true);
    }

    /**
     * DataTable: 批次明细 (inventory_batches + inventory_item)
     */
    public function batchDetail(Request $request)
    {
        $query = InventoryBatch::with(['inventoryItem.category'])
            ->whereNull('inventory_batches.deleted_at')
            ->where('qty', '>', 0);

        if ($request->filled('category_id')) {
            $query->whereHas('inventoryItem', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $expiryStatus = $request->input('expiry_status', '');
        if ($expiryStatus === 'expired') {
            $query->where(function ($q) {
                $q->where('status', 'expired')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('expiry_date')
                         ->where('expiry_date', '<', Carbon::now());
                  });
            });
        } elseif ($expiryStatus === 'near') {
            $query->where('status', 'available')
                  ->whereNotNull('expiry_date')
                  ->where('expiry_date', '>=', Carbon::now())
                  ->where('expiry_date', '<=', Carbon::now()->addDays(30));
        } elseif ($expiryStatus === 'normal') {
            $query->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', Carbon::now()->addDays(30));
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('item_name_display', function ($row) {
                return $row->inventoryItem ? $row->inventoryItem->name : '-';
            })
            ->addColumn('category_name', function ($row) {
                if ($row->inventoryItem && $row->inventoryItem->category) {
                    return $row->inventoryItem->category->name;
                }
                return '-';
            })
            ->addColumn('expiry_badge', function ($row) {
                if (!$row->expiry_date) {
                    return '<span class="badge badge-secondary">' . __('inventory.expiry_normal') . '</span>';
                }
                $days = Carbon::now()->diffInDays($row->expiry_date, false);
                if ($days < 0) {
                    return '<span class="badge badge-danger">' . __('inventory.expiry_expired') . '</span>';
                } elseif ($days <= 30) {
                    return '<span class="badge badge-warning">' . __('inventory.expiry_near') . '</span>';
                }
                return '<span class="badge badge-success">' . __('inventory.expiry_normal') . '</span>';
            })
            ->addColumn('expiry_date_display', function ($row) {
                return $row->expiry_date ? $row->expiry_date->format('Y-m-d') : '-';
            })
            ->addColumn('qty_display', function ($row) {
                return number_format((float) $row->qty, 2);
            })
            ->addColumn('created_at_display', function ($row) {
                return $row->created_at ? $row->created_at->format('Y-m-d') : '-';
            })
            ->rawColumns(['expiry_badge'])
            ->make(true);
    }

    /**
     * DataTable: 出入库查询（按物品聚合，日期范围内）
     */
    public function movementSummary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $keyword   = $request->input('keyword', '');

        // Build stock-in totals per item
        $inQuery = StockInItem::select(
                'inventory_item_id',
                DB::raw('SUM(qty) as total_in_qty')
            )
            ->whereNull('stock_in_items.deleted_at')
            ->whereHas('stockIn', function ($q) use ($startDate, $endDate) {
                $q->where('status', StockIn::STATUS_CONFIRMED)
                  ->whereNull('deleted_at');
                if ($startDate) {
                    $q->where('stock_in_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('stock_in_date', '<=', $endDate);
                }
            })
            ->groupBy('inventory_item_id');

        // Build stock-out totals per item
        $outQuery = StockOutItem::select(
                'inventory_item_id',
                DB::raw('SUM(qty) as total_out_qty')
            )
            ->whereNull('stock_out_items.deleted_at')
            ->whereHas('stockOut', function ($q) use ($startDate, $endDate) {
                $q->where('status', StockOut::STATUS_CONFIRMED)
                  ->whereNull('deleted_at');
                if ($startDate) {
                    $q->where('stock_out_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('stock_out_date', '<=', $endDate);
                }
            })
            ->groupBy('inventory_item_id');

        // Main query: items with at least one movement in the period.
        // LEFT JOIN the aggregate subqueries so totals are resolved in SQL
        // (eliminates 3N per-row queries in the DataTable callbacks).
        $query = InventoryItem::query()
            ->select([
                'inventory_items.*',
                DB::raw('COALESCE(ins.total_in_qty, 0) as total_in_qty'),
                DB::raw('COALESCE(outs.total_out_qty, 0) as total_out_qty'),
            ])
            ->leftJoinSub($inQuery, 'ins', 'ins.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoinSub($outQuery, 'outs', 'outs.inventory_item_id', '=', 'inventory_items.id')
            ->with('category')
            ->whereNull('inventory_items.deleted_at')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereHas('stockInItems', function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('stock_in_items.deleted_at')
                       ->whereHas('stockIn', function ($q3) use ($startDate, $endDate) {
                           $q3->where('status', StockIn::STATUS_CONFIRMED)
                              ->whereNull('deleted_at');
                           if ($startDate) {
                               $q3->where('stock_in_date', '>=', $startDate);
                           }
                           if ($endDate) {
                               $q3->where('stock_in_date', '<=', $endDate);
                           }
                       });
                })->orWhereHas('stockOutItems', function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('stock_out_items.deleted_at')
                       ->whereHas('stockOut', function ($q3) use ($startDate, $endDate) {
                           $q3->where('status', StockOut::STATUS_CONFIRMED)
                              ->whereNull('deleted_at');
                           if ($startDate) {
                               $q3->where('stock_out_date', '>=', $startDate);
                           }
                           if ($endDate) {
                               $q3->where('stock_out_date', '<=', $endDate);
                           }
                       });
                });
            });

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('item_code', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category_name', function ($row) {
                return $row->category ? $row->category->name : '-';
            })
            ->addColumn('item_name_display', function ($row) {
                return $row->name;
            })
            ->addColumn('total_stock_in_qty', function ($row) {
                return number_format((float) $row->total_in_qty, 2);
            })
            ->addColumn('total_stock_out_qty', function ($row) {
                return number_format((float) $row->total_out_qty, 2);
            })
            ->addColumn('net_change', function ($row) {
                $net = bcsub((string)(float)$row->total_in_qty, (string)(float)$row->total_out_qty, 2);
                $class = (float) $net >= 0 ? 'text-success' : 'text-danger';
                return '<span class="' . $class . '">' . $net . '</span>';
            })
            ->rawColumns(['net_change'])
            ->make(true);
    }

    /**
     * DataTable: 出入库明细（流水，每笔记录）
     */
    public function movementDetail(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $outType   = $request->input('out_type');
        $status    = $request->input('status');
        $keyword   = $request->input('keyword', '');

        // Build unified stock-in records
        $stockIns = StockIn::select(
                DB::raw("'stock_in' as movement_type"),
                'stock_ins.id',
                'stock_ins.stock_in_no as record_no',
                'stock_ins.stock_in_date as movement_date',
                'stock_ins.status',
                DB::raw('NULL as out_type'),
                'stock_ins.created_at'
            )
            ->whereNull('stock_ins.deleted_at');

        if ($status) {
            $stockIns->where('stock_ins.status', $status);
        }
        if ($startDate) {
            $stockIns->where('stock_in_date', '>=', $startDate);
        }
        if ($endDate) {
            $stockIns->where('stock_in_date', '<=', $endDate);
        }
        // out_type filter only applies to stock-outs, so skip for ins
        if ($outType) {
            $stockIns->whereRaw('1=0');
        }

        // Build unified stock-out records
        $stockOuts = StockOut::select(
                DB::raw("'stock_out' as movement_type"),
                'stock_outs.id',
                'stock_outs.stock_out_no as record_no',
                'stock_outs.stock_out_date as movement_date',
                'stock_outs.status',
                'stock_outs.out_type',
                'stock_outs.created_at'
            )
            ->whereNull('stock_outs.deleted_at');

        if ($status) {
            $stockOuts->where('stock_outs.status', $status);
        }
        if ($startDate) {
            $stockOuts->where('stock_out_date', '>=', $startDate);
        }
        if ($endDate) {
            $stockOuts->where('stock_out_date', '<=', $endDate);
        }
        if ($outType) {
            $stockOuts->where('out_type', $outType);
        }

        // For movement detail, we show each header record with item info
        // Since UNION is complex with Eloquent+DataTables, we use separate item-level queries.
        // We query stock_in_items joined with stock_ins for in-records
        // and stock_out_items joined with stock_outs for out-records, then union.

        $inItemsQuery = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->join('inventory_items', 'stock_in_items.inventory_item_id', '=', 'inventory_items.id')
            ->select(
                DB::raw("'stock_in' as movement_type"),
                'stock_ins.stock_in_no as record_no',
                'stock_ins.stock_in_date as movement_date',
                'stock_ins.status',
                DB::raw('NULL as out_type'),
                'inventory_items.name as item_name',
                'inventory_items.item_code',
                'stock_in_items.qty',
                'stock_in_items.created_at'
            )
            ->whereNull('stock_in_items.deleted_at')
            ->whereNull('stock_ins.deleted_at')
            ->whereNull('inventory_items.deleted_at');

        if ($status) {
            $inItemsQuery->where('stock_ins.status', $status);
        }
        if ($startDate) {
            $inItemsQuery->where('stock_ins.stock_in_date', '>=', $startDate);
        }
        if ($endDate) {
            $inItemsQuery->where('stock_ins.stock_in_date', '<=', $endDate);
        }
        if ($outType) {
            // out_type only applies to stock-out, exclude all stock-in if filtering
            $inItemsQuery->whereRaw('1=0');
        }
        if ($keyword) {
            $inItemsQuery->where(function ($q) use ($keyword) {
                $q->where('inventory_items.name', 'like', "%{$keyword}%")
                  ->orWhere('inventory_items.item_code', 'like', "%{$keyword}%");
            });
        }

        $outItemsQuery = DB::table('stock_out_items')
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->join('inventory_items', 'stock_out_items.inventory_item_id', '=', 'inventory_items.id')
            ->select(
                DB::raw("'stock_out' as movement_type"),
                'stock_outs.stock_out_no as record_no',
                'stock_outs.stock_out_date as movement_date',
                'stock_outs.status',
                'stock_outs.out_type',
                'inventory_items.name as item_name',
                'inventory_items.item_code',
                'stock_out_items.qty',
                'stock_out_items.created_at'
            )
            ->whereNull('stock_out_items.deleted_at')
            ->whereNull('stock_outs.deleted_at')
            ->whereNull('inventory_items.deleted_at');

        if ($status) {
            $outItemsQuery->where('stock_outs.status', $status);
        }
        if ($startDate) {
            $outItemsQuery->where('stock_outs.stock_out_date', '>=', $startDate);
        }
        if ($endDate) {
            $outItemsQuery->where('stock_outs.stock_out_date', '<=', $endDate);
        }
        if ($outType) {
            $outItemsQuery->where('stock_outs.out_type', $outType);
        }
        if ($keyword) {
            $outItemsQuery->where(function ($q) use ($keyword) {
                $q->where('inventory_items.name', 'like', "%{$keyword}%")
                  ->orWhere('inventory_items.item_code', 'like', "%{$keyword}%");
            });
        }

        $union = $inItemsQuery->union($outItemsQuery)->orderBy('movement_date', 'desc');

        return DataTables::of($union)
            ->addIndexColumn()
            ->addColumn('type_badge', function ($row) {
                if ($row->movement_type === 'stock_in') {
                    return '<span class="badge badge-success">' . __('inventory.stock_in_record') . '</span>';
                }
                return '<span class="badge badge-warning">' . __('inventory.stock_out_record') . '</span>';
            })
            ->addColumn('out_type_label', function ($row) {
                if ($row->movement_type === 'stock_out' && $row->out_type) {
                    return \App\DictItem::nameByCode('stock_out_type', $row->out_type) ?? $row->out_type;
                }
                return '-';
            })
            ->addColumn('status_badge', function ($row) {
                $statusClasses = [
                    'draft'     => 'badge-secondary',
                    'confirmed' => 'badge-success',
                    'cancelled' => 'badge-danger',
                ];
                $class = $statusClasses[$row->status] ?? 'badge-default';
                $dictType = $row->movement_type === 'stock_in' ? 'stock_in_status' : 'stock_out_status';
                $label = \App\DictItem::nameByCode($dictType, $row->status) ?? $row->status;
                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('qty_display', function ($row) {
                return number_format((float) $row->qty, 2);
            })
            ->rawColumns(['type_badge', 'status_badge'])
            ->make(true);
    }
}
