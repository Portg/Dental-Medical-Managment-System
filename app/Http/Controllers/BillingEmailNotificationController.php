<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\BillingEmailNotificationService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class BillingEmailNotificationController extends Controller
{
    private BillingEmailNotificationService $billingEmailNotificationService;

    public function __construct(BillingEmailNotificationService $billingEmailNotificationService)
    {
        $this->billingEmailNotificationService = $billingEmailNotificationService;
        $this->middleware('can:manage-settings');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->billingEmailNotificationService->getList($request->only([
                'search', 'start_date', 'end_date',
            ]));

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('message', function ($row) {
                    return $row->message;
                })
                ->addColumn('notification_type', function ($row) {
                    $type = '';
                    if ($row->notification_type == "Invoice") {
                        $type = '<span class="label label-sm label-danger">' . $row->notification_type . '</span>';
                    } else if ($row->notification_type == "Quotation") {
                        $type = '<span class="label label-sm label-success">' . $row->notification_type . '</span>';
                    } else {
                        $type = '<span class="label label-sm label-info">' . $row->notification_type . '</span>';
                    }
                    return $type;
                })
                ->rawColumns(['message', 'notification_type'])
                ->make(true);
        }
        return view('billing_email_notifications.index');
    }
}
