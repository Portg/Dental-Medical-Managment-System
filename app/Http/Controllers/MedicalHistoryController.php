<?php

namespace App\Http\Controllers;

use App\Services\MedicalHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MedicalHistoryController extends Controller
{
    private MedicalHistoryService $service;

    public function __construct(MedicalHistoryService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-patients');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $patient_id
     * @return Response
     */
    public function index(Request $request, $patient_id)
    {
        $data = $this->service->getMedicalHistoryForPatient((int) $patient_id);

        return view('medical_history.index', [
            'patient' => $data['patient'],
            'medical_cards' => $data['medical_cards'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
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
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
