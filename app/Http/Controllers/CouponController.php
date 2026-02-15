<?php

namespace App\Http\Controllers;

use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class CouponController extends Controller
{
    private CouponService $service;

    public function __construct(CouponService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of coupons.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getCouponList();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type_display', function ($row) {
                    return $row->type === 'fixed'
                        ? __('invoices.coupon_fixed') . ' ¥' . number_format($row->value, 2)
                        : __('invoices.coupon_percent') . ' ' . $row->value . '%';
                })
                ->addColumn('status_display', function ($row) {
                    if (!$row->is_active) {
                        return '<span class="label label-default">' . __('common.inactive') . '</span>';
                    }
                    if ($row->expires_at && $row->expires_at < now()) {
                        return '<span class="label label-warning">' . __('invoices.coupon_expired') . '</span>';
                    }
                    if ($row->max_uses && $row->used_count >= $row->max_uses) {
                        return '<span class="label label-danger">' . __('invoices.coupon_exhausted') . '</span>';
                    }
                    return '<span class="label label-success">' . __('common.active') . '</span>';
                })
                ->addColumn('usage_display', function ($row) {
                    $used = $row->used_count ?? 0;
                    $max = $row->max_uses ? $row->max_uses : '∞';
                    return $used . ' / ' . $max;
                })
                ->addColumn('validity_display', function ($row) {
                    if ($row->starts_at && $row->expires_at) {
                        return $row->starts_at->format('Y-m-d') . ' ~ ' . $row->expires_at->format('Y-m-d');
                    } elseif ($row->expires_at) {
                        return __('common.until') . ' ' . $row->expires_at->format('Y-m-d');
                    }
                    return __('common.unlimited');
                })
                ->addColumn('actions', function ($row) {
                    $editBtn = '<a href="#" onclick="editCoupon(' . $row->id . ')" class="btn btn-sm btn-primary"><i class="icon-pencil"></i></a>';
                    $deleteBtn = '<a href="#" onclick="deleteCoupon(' . $row->id . ')" class="btn btn-sm btn-danger"><i class="icon-trash"></i></a>';
                    return $editBtn . ' ' . $deleteBtn;
                })
                ->rawColumns(['status_display', 'actions'])
                ->make(true);
        }

        return view('coupons.index');
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create()
    {
        return view('coupons.create');
    }

    /**
     * Store a newly created coupon.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        // Validate percent value
        if ($request->type === 'percent' && $request->value > 100) {
            return response()->json([
                'message' => __('invoices.coupon_percent_max'),
                'status' => false
            ]);
        }

        $coupon = $this->service->createCoupon(array_merge($request->all(), [
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]));

        return response()->json([
            'message' => __('invoices.coupon_created_successfully'),
            'status' => true,
            'coupon' => $coupon
        ]);
    }

    /**
     * Display the specified coupon.
     */
    public function show($id)
    {
        return response()->json($this->service->getCouponDetail($id));
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit($id)
    {
        return response()->json($this->service->getCouponForEdit($id));
    }

    /**
     * Update the specified coupon.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code,' . $id,
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        if ($request->type === 'percent' && $request->value > 100) {
            return response()->json([
                'message' => __('invoices.coupon_percent_max'),
                'status' => false
            ]);
        }

        $this->service->updateCoupon($id, array_merge($request->all(), [
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]));

        return response()->json([
            'message' => __('invoices.coupon_updated_successfully'),
            'status' => true
        ]);
    }

    /**
     * Remove the specified coupon.
     */
    public function destroy($id)
    {
        $this->service->deleteCoupon($id);

        return response()->json([
            'message' => __('invoices.coupon_deleted_successfully'),
            'status' => true
        ]);
    }

    /**
     * Validate a coupon code.
     */
    public function validateCoupon(Request $request, $code)
    {
        $result = $this->service->validateCoupon(
            $code,
            $request->order_amount ?? 0,
            $request->patient_id
        );

        return response()->json($result);
    }
}
