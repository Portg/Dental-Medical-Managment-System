<?php

namespace App\Http\Controllers;

use App\MedicalCard;
use App\Services\MedicalCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class MedicalCardController extends Controller
{
    private MedicalCardService $medicalCardService;

    public function __construct(MedicalCardService $medicalCardService)
    {
        $this->medicalCardService = $medicalCardService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->medicalCardService->getMedicalCardList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                    if (!empty($request->get('search'))) {
                        $instance->collection = $instance->collection->filter(function ($row) use ($request) {
                            if (Str::contains(Str::lower(\App\Http\Helper\NameHelper::join($row['surname'], $row['othername'])), Str::lower($request->get('search'))
                            )) {
                                return true;
                            }
                            return false;
                        });
                    }
                })
                ->addColumn('patient', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('added_by', function ($row) {
                    return $row->added_by;
                })
                ->addColumn('view_cards', function ($row) {
                    $btn = '<a href="' . url('medical-cards/' . $row->id) . '" class="btn btn-primary">' . __('medical_cards.view_cards') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->addColumn('checkbox', '<input type="checkbox" name="student_checkbox[]" class="student_checkbox" value="{{$id}}" />')
                ->rawColumns(['view_cards', 'deleteBtn', 'checkbox'])
                ->make(true);
        }
        return view('medical_cards.index');
    }

    public function individualMedicalCards(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->medicalCardService->getIndividualMedicalCards($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('card_link', function ($row) {
                    $btn = '
                    <a class="image-popup-vertical-fit" target="_blank"  href="' . asset('uploads/medical_cards/' .
                            $row->card_link) . '">
	                   <img src="' . asset('uploads/medical_cards/' . $row->card_link) . '" width="75" height="75">
                   </a>
                    ';
                    return $btn;
                })
                ->rawColumns(['card_link'])
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
        request()->validate([
            'patient_id' => 'required',
            'card_type' => 'required',
            'uploadFile' => 'required',
        ]);

        $card = $this->medicalCardService->createMedicalCard(
            $request->only('card_type', 'patient_id'),
            $request->file('uploadFile')
        );

        if ($card) {
            return redirect('/medical-cards/' . $card->id);
        }
        return Response()->json(["message" => __('messages.error_occurred_later'), "status" => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = $this->medicalCardService->getMedicalCardDetail($id);
        return view('medical_cards.show')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->medicalCardService->getMedicalCardForEdit($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\MedicalCard $medicalCard
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MedicalCard $medicalCard)
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
        $status = $this->medicalCardService->deleteMedicalCard($id);
        if ($status) {
            return Response()->json(["message" => __('medical_cards.medical_card_deleted_successfully'), "status" => true]);
        }
        return Response()->json(["message" => __('messages.error_occurred_later'), "status" => true]);
    }

    function massremove(Request $request)
    {
        $student_id_array = $request->input('id');
        $student = Student::whereIn('id', $student_id_array);
        if ($student->delete()) {
            echo __('messages.data_deleted');
        }
    }
}
