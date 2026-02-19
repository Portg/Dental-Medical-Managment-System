<?php

namespace App\Http\Controllers;

use App\Services\MedicalCardItemService;
use Illuminate\Http\Request;

class MedicalCardItemController extends Controller
{
    private MedicalCardItemService $service;

    public function __construct(MedicalCardItemService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-medical-services');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
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
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->service->deleteMedicalCardItem($id);
        if ($status) {
            return response()->json(["message" => __('messages.medical_card_deleted_successfully'), "status" => true]);
        }
        return response()->json(["message" => __('messages.error_try_again'), "status" => false]);
    }
}
