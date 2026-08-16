<?php

namespace App\Http\Controllers;

use App\Services\QuotationItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class QuotationItemController extends Controller
{
    private QuotationItemService $service;

    public function __construct(QuotationItemService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-quotations');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $quotation_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $quotation_id)
    {
        if ($request->ajax()) {
            $data = $this->service->getListByQuotation((int) $quotation_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('service', function ($row) {
                    return $row->name;
                })
                ->addColumn('qty', function ($row) {
                    return number_format($row->qty);
                })
                // 明细表声明了 tooth_no 这一列（见 quotations_show_index.js）。
                // 服务端不给，DataTables 会抛「Requested unknown parameter」，
                // 报价单详情页只要有一条明细就整表弹错。
                ->addColumn('tooth_no', function ($row) {
                    return $row->tooth_no ?? '-';
                })
                // 金额列叫 amount，存的是单价（见 QuotationItemService::create）
                ->addColumn('price', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('total_amount', function ($row) {
                    return number_format($row->qty * $row->amount);
                })
                ->addColumn('added_by', function ($row) {
                    return $row->othername;
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteItem(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            // quotation_id 此前既没校验也没往下传，而 QuotationItemService::create()
            // 必读这个键 —— 报价单详情页点「追加项目」必然是 500（PHP 8 下
            // 未定义数组键会被 Laravel 的错误处理器抛成 ErrorException）。
            'quotation_id' => 'required|integer|exists:quotations,id',
            'price' => 'required|numeric|min:0',
            'qty' => 'required|numeric|min:0.01',
            'tooth_no' => 'nullable|string|max:50',
            'medical_service_id' => 'required|integer|exists:medical_services,id',
        ])->validate();

        $status = $this->service->create($request->only([
            'price', 'qty', 'tooth_no', 'quotation_id', 'medical_service_id',
        ]));

        if ($status) {
            return response()->json(['message' => __('invoices.quotation_item_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $quotationItem_id
     * @return \Illuminate\Http\Response
     */
    public function edit($quotationItem_id)
    {
        return response()->json($this->service->find((int) $quotationItem_id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'qty' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'tooth_no' => 'nullable|string|max:50',
            'medical_service_id' => 'required|integer|exists:medical_services,id',
        ])->validate();

        $status = $this->service->update((int) $id, $request->only([
            'qty', 'price', 'tooth_no', 'medical_service_id',
        ]));

        if ($status) {
            return response()->json(['message' => __('invoices.quotation_item_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $quotationItem_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($quotationItem_id)
    {
        $status = $this->service->delete((int) $quotationItem_id);

        if ($status) {
            return response()->json(['message' => __('invoices.quotation_item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
