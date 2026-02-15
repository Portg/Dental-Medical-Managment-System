<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Jobs\ShareEmailQuotation;
use App\QuotationItem;
use App\Services\QuotationService;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class QuotationController extends Controller
{
    private QuotationService $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->quotationService->getQuotationList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('quotation_no', function ($row) {
                    return '<a href="' . url('quotations/' . $row->id) . '">' . $row->quotation_no . '</a>';
                })
                ->addColumn('customer', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('amount', function ($row) {
                    return number_format(QuotationItem::where('quotation_id', $row->id)->sum(DB::raw('qty*price')));
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="' . url('quotations/' . $row->id) . '"> ' . __('common.view') . ' </a>
                            </li>
                             <li>
                                <a target="_blank" href="' . url('print-quotation/' . $row->id) . '"  > ' . __('common.print') . ' </a>
                            </li>
                              <li>
                                <a href="#" onclick="shareQuotationView(' . $row->id . ')"  > ' . __('common.share_quotation') . ' </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['quotation_no', 'action', 'status'])
                ->make(true);
        }
        return view('quotations.index');
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
            'patient_id' => 'required'
        ], [
            'patient_id.required' => __('validation.custom.patient_id.required')
        ])->validate();

        $quotation = $this->quotationService->createQuotation(
            $request->patient_id,
            $request->addmore,
            Auth::User()->id
        );

        if ($quotation) {
            return response()->json(['message' => __('invoices.quotation_created_successfully'), 'status' => true]);
        }

        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Quotation $quotation
     * @return \Illuminate\Http\Response
     */
    public function show($quotation)
    {
        $data = $this->quotationService->getQuotationShowData($quotation);
        return view('quotations.show.index')->with($data);
    }


    /**
     * @param $quotation_id
     * @return mixed
     */
    public function printQuotation($quotation_id)
    {
        $data = $this->quotationService->getQuotationPrintData($quotation_id);

        $pdf = PDF::loadView('quotations.print_quotation', $data);
        return $pdf->stream('receipt', array("attachment" => false))->header('Content-Type', 'application/pdf');
    }

    public function quotationShareDetails(Request $request, $quotation_id)
    {
        return response()->json($this->quotationService->getShareDetails($quotation_id));
    }

    public function sendQuotation(Request $request)
    {
        Validator::make($request->all(), [
            'quotation_id' => 'required',
            'email' => 'required'
        ])->validate();

        $data = $this->quotationService->getQuotationPrintData($request->quotation_id);

        dispatch(new ShareEmailQuotation($data, $request->email, $request->message));
        return response()->json(['message' => __('emails.quotation_sent_successfully'), 'status' => true]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Quotation $quotation
     * @return \Illuminate\Http\Response
     */
    public function edit($quotation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Quotation $quotation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $quotation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Quotation $quotation
     * @return \Illuminate\Http\Response
     */
    public function destroy($quotation)
    {
        //
    }
}
