<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\BookAppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookAppointmentController extends Controller
{
    private BookAppointmentService $bookAppointmentService;

    public function __construct(BookAppointmentService $bookAppointmentService)
    {
        $this->bookAppointmentService = $bookAppointmentService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('frontend/book_appointment');
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
            'full_name' => 'required',
            'phone_number' => 'required',
            'message' => 'required'
        ])->validate();

        $success = $this->bookAppointmentService->create(
            $request->full_name,
            $request->phone_number,
            $request->email,
            $request->message
        );

        return FunctionsHelper::messageResponse(__('messages.booking_request_sent_successfully'), $success);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
