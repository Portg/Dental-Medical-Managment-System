<?php

namespace App\Http\Controllers;

use App\Services\QuickPhraseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // search 走 edit-patients：快捷短语浮层绑在 .phrase-enabled 输入框上
        // （template_picker.js 的 QuickPhrasePicker），那几个 class 只出现在书写页，
        // 其准入正是 edit-patients。manage-settings 仅超管与管理员持有，
        // 挂它的话医生、护士、前台在书写页敲字时一律拿不到短语。
        //
        // 写入口（增删改）的权限**按 scope 分**，不能在中间件里一刀切 ——
        // 中间件看不到 scope。见 authorizeWrite()：
        //     personal → edit-patients，医生能管自己那套
        //     system   → manage-settings，全院共用的不该谁都能改
        // 原来整个写入口都挂 manage-settings，等于医生改不了短语库，
        // 而 scope 里那个 personal（医生的私人短语）根本创建不出来。
        $this->middleware('can:edit-patients')->only(['index', 'show', 'search', 'store', 'update', 'destroy']);
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
     * 写操作的准入，按 scope 分。
     *
     *   system   —— 全院共用，改一条所有医生都受影响，归 manage-settings
     *   personal —— 医生自己那套，归 edit-patients，且只能动自己的
     *
     * $existing 传已有记录时会一并检查它 —— 否则医生可以把别人的私人短语改成
     * 自己的，或者把一条 system 短语「改成」personal 从而绕开 manage-settings。
     */
    private function authorizeWrite(string $targetScope, ?object $existing = null): void
    {
        foreach (array_filter([$targetScope, $existing->scope ?? null]) as $scope) {
            if ($scope === 'system') {
                $this->authorize('manage-settings');
            }
        }

        // 私人短语只能动自己的。system 短语没有归属，走上面的 manage-settings。
        if (($existing->scope ?? null) === 'personal'
            && (int) ($existing->user_id ?? 0) !== (int) Auth::id()) {
            abort(403);
        }
    }

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
        $this->authorizeWrite($request->scope);

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
        $this->authorizeWrite($request->scope, $this->service->getPhrase((int) $id));

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
        $existing = $this->service->getPhrase((int) $id);
        // 删除只看已有记录的 scope（没有「目标 scope」这回事）
        $this->authorizeWrite($existing->scope, $existing);

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
