<?php

namespace App\Http\Controllers;

use App\Services\ChairsideService;
use App\Services\MedicalTreatmentService;
use Illuminate\Http\Request;

class MedicalTreatmentController extends Controller
{
    private MedicalTreatmentService $service;

    private ChairsideService $chairside;

    public function __construct(MedicalTreatmentService $service, ChairsideService $chairside)
    {
        $this->service = $service;
        $this->chairside = $chairside;
        $this->middleware('can:edit-patients');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $appointment_id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $appointment_id)
    {
        $data = $this->service->getTreatmentDataForAppointment((int) $appointment_id);

        // 椅旁工作台：患者警示 / 两个方向的钱 / 治疗史 / 计划待办。
        // 都是已有数据的聚合，见 ChairsideService 的说明。
        if (!empty($data['patient'])) {
            $patient = $data['patient'];
            $data['chairside'] = [
                'bar'    => $this->chairside->patientBar($patient),
                'visits' => $this->chairside->visitHistory($patient->id),
                'plan'   => $this->chairside->pendingPlan($patient->id),
            ];
        }

        // medical-treatment/{id} 不在侧栏菜单中，breadcrumb-auto 匹配不到时
        // 布局会落到硬编码的「今日工作」——此处显式设置面包屑。
        $data['breadcrumb_parent'] = __('menu.group_appointment_management');
        $data['breadcrumb_parent_url'] = url('appointments');
        $data['breadcrumb_current'] = __('medical_treatment.page_title');

        return view('medical_treatment.index')->with($data);
    }

    /**
     * 某颗牙历次做过什么（AJAX）—— 点牙位图时右栏换成这颗牙的时间线。
     */
    public function toothHistory(Request $request, $patientId)
    {
        $tooth = trim((string) $request->query('tooth', ''));

        return response()->json([
            'status' => true,
            'tooth'  => $tooth,
            'data'   => $tooth === ''
                ? $this->chairside->visitHistory((int) $patientId)
                : $this->chairside->toothHistory((int) $patientId, $tooth),
        ]);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
