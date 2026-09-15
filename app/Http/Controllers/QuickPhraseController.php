<?php

namespace App\Http\Controllers;

use App\Services\QuickPhraseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class QuickPhraseController extends Controller
{
    /**
     * 短语能挂到病历的哪些字段。与 QuickPhrase::panelForUser 取数用的 category 对齐 ——
     * 对不上的值进不了锚定面板。
     */
    public const CATEGORIES = [
        'chief_complaint', 'present_illness', 'past_history',
        'examination', 'auxiliary_examination', 'diagnosis',
        'treatment_plan', 'treatment', 'medical_orders',
    ];

    private QuickPhraseService $service;

    public function __construct(QuickPhraseService $service)
    {
        $this->service = $service;
        // search 改用 edit-patients 把关：快捷短语浮层绑定在 .phrase-enabled 输入框上
        // （template_picker.js 中的 QuickPhrasePicker），该 class 只出现在诊断、治疗计划、
        // 病程记录三个书写页，其准入正是 edit-patients。manage-settings 仅超管与管理员
        // 持有，医生、护士、前台在书写页敲字时一律拿不到短语。
        // 写入口（增删改）仍由 manage-settings 把关。
        $this->middleware('can:manage-settings')->except(['search']);
        $this->middleware('can:edit-patients')->only(['search']);
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
                    // 走 medical_cases.phrase_category_* —— 与病历页侧栏、锚定面板
                    // 同一套标签。原来这里写死四个（examination/diagnosis/treatment/
                    // other），新增的六个字段会直接把英文 slug 显示出来。
                    $key = 'medical_cases.phrase_category_' . $row->category;
                    $label = __($key);
                    return $label === $key ? $row->category : $label;
                })
                ->addColumn('slot_label', function ($row) {
                    // 没归槽位的（诊所早先加的那些）在面板里会落到「其他」组
                    return $row->slot ?: '<span class="text-muted">' . __('common.other') . '</span>';
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
                ->rawColumns(['slot_label', 'scope_label', 'status', 'action'])
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
    /**
     * 短语的校验规则。
     *
     * shortcut 改成选填：它是 ; 选择器认的简写，而临床短语多为中文、量又大
     * （现病史一栏就 56 条），逐条编简写既没人记得住也容易撞。库里 439 条系统
     * 短语的 shortcut 全是空的 —— 必填的话医生一改就被拦下。
     *
     * category 收紧到病历的九个字段：锚定短语面板按 category 取这一段的短语
     * （见 QuickPhrase::panelForUser），值对不上就进不了面板，医生会以为短语丢了。
     */
    private function rules(): array
    {
        return [
            'shortcut' => 'nullable|string|max:20',
            'phrase'   => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', self::CATEGORIES),
            // 槽位是自由文本：打一个已有的名字就并进那一组，打新的就开一组。
            // 不做成固定枚举 —— 诊所按自己的写法分组，比我们预设的更贴。
            'slot'     => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'scope'    => 'required|in:system,personal',
        ];
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), $this->rules())->validate();

        $phrase = $this->service->createPhrase([
            'shortcut' => (string) $request->shortcut,
            'phrase' => $request->phrase,
            'category' => $request->category,
            'slot' => $request->filled('slot') ? trim($request->slot) : null,
            'sort_order' => (int) $request->input('sort_order', 0),
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
        Validator::make($request->all(), $this->rules())->validate();

        $status = $this->service->updatePhrase((int) $id, [
            'shortcut' => (string) $request->shortcut,
            'phrase' => $request->phrase,
            'category' => $request->category,
            'slot' => $request->filled('slot') ? trim($request->slot) : null,
            'sort_order' => (int) $request->input('sort_order', 0),
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
