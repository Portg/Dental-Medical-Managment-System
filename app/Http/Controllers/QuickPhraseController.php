<?php

namespace App\Http\Controllers;

use App\Services\QuickPhraseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class QuickPhraseController extends Controller
{
    private QuickPhraseService $service;

    public function __construct(QuickPhraseService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-settings');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getPhraseList($request->only(['category', 'scope']));

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('category_label', function ($row) {
                    $labels = [
                        'examination' => __('templates.examination'),
                        'diagnosis' => __('templates.diagnosis'),
                        'treatment' => __('templates.treatment'),
                        'other' => __('templates.other'),
                    ];
                    return $labels[$row->category] ?? $row->category;
                })
                ->addColumn('scope_label', function ($row) {
                    if ($row->scope === 'system') {
                        return '<span class="label label-primary">' . __('templates.system') . '</span>';
                    }
                    return '<span class="label label-default">' . __('templates.personal') . '</span>';
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="text-primary">' . __('common.active') . '</span>';
                    }
                    return '<span class="text-danger">' . __('common.inactive') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                            ' . __('common.action') . '
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="editPhrase(' . $row->id . ')">' . __('common.edit') . '</a>
                            </li>
                            <li>
                                <a href="#" onclick="deletePhrase(' . $row->id . ')">' . __('common.delete') . '</a>
                            </li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['scope_label', 'status', 'action'])
                ->make(true);
        }

        return view('quick_phrases.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'shortcut' => 'required|string|max:20',
            'phrase' => 'required|string|max:255',
            'scope' => 'required|in:system,personal',
        ])->validate();

        $phrase = $this->service->createPhrase([
            'shortcut' => $request->shortcut,
            'phrase' => $request->phrase,
            'category' => $request->category,
            'scope' => $request->scope,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        if ($phrase) {
            return response()->json([
                'message' => __('messages.phrase_created_successfully'),
                'status' => true,
                'data' => $phrase
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $phrase = $this->service->getPhrase((int) $id);
        return response()->json([
            'status' => true,
            'data' => $phrase
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'shortcut' => 'required|string|max:20',
            'phrase' => 'required|string|max:255',
            'scope' => 'required|in:system,personal',
        ])->validate();

        $status = $this->service->updatePhrase((int) $id, [
            'shortcut' => $request->shortcut,
            'phrase' => $request->phrase,
            'category' => $request->category,
            'scope' => $request->scope,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        if ($status) {
            return response()->json([
                'message' => __('messages.phrase_updated_successfully'),
                'status' => true
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $status = $this->service->deletePhrase((int) $id);

        if ($status) {
            return response()->json([
                'message' => __('messages.phrase_deleted_successfully'),
                'status' => true
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }

    /**
     * Search phrases for quick insertion.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $phrases = $this->service->searchPhrases($request->get('q', ''), $request->get('category'));

        return response()->json([
            'status' => true,
            'data' => $phrases
        ]);
    }
}
