<div class="modal fade modal-form" id="phrase-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('templates.create_phrase') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"></div>
                <form action="#" id="phrase-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="phrase_id" name="id">

                    {{-- 简写改成选填：它是 ; 选择器认的键，而临床短语多为中文、量又大
                         （现病史一栏 56 条），逐条编简写既没人记得住也容易撞。
                         系统自带的 439 条 shortcut 全是空的。 --}}
                    @include('components.form.text-field', [
                        'name' => 'shortcut',
                        'label' => __('templates.shortcut'),
                        'required' => false,
                        'maxlength' => 20,
                        'placeholder' => __('templates.shortcut_hint'),
                    ])

                    @include('components.form.text-field', [
                        'name' => 'phrase',
                        'label' => __('templates.phrase'),
                        'required' => true,
                        'placeholder' => __('templates.phrase_hint'),
                    ])

                    {{-- 挂到病历的哪一段。锚定短语面板按这个值取短语
                         （QuickPhrase::panelForUser），选错了就进不了面板。
                         原来只有检查/诊断/治疗/其他四个值，新增的六个字段选不到。 --}}
                    @include('components.form.select-field', [
                        'name' => 'category',
                        'label' => __('templates.category'),
                        'required' => true,
                        'placeholder' => __('common.select'),
                        'options' => collect(\App\Http\Controllers\QuickPhraseController::CATEGORIES)
                            ->map(fn ($c) => [
                                'value' => $c,
                                'text'  => __('medical_cases.phrase_category_' . $c),
                            ])->all(),
                    ])

                    {{-- 语义槽位：短语在面板里归到哪一组。

                         打一个已有的名字就并进那一组，打新的就开一组 —— 不做成固定
                         枚举，诊所按自己的写法分组比我们预设的更贴。下拉里列的是
                         库里已有的槽位，省得手打错字开出一个只有一条的新组。 --}}
                    <div class="form-group">
                        <label class="control-label col-md-3">{{ __('medical_cases.phrase_slot') }}</label>
                        <div class="col-md-9">
                            <input type="text" name="slot" id="slot" class="form-control"
                                   maxlength="40" list="phrase-slot-options"
                                   placeholder="{{ __('medical_cases.phrase_slot_hint') }}">
                            <datalist id="phrase-slot-options">
                                @foreach(($existingSlots ?? []) as $slot)
                                    <option value="{{ $slot }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    {{-- 槽位内的排序。「1天前」必须排在「1周前」前面，靠 id 排不住。 --}}
                    @include('components.form.text-field', [
                        'name' => 'sort_order',
                        'label' => __('medical_cases.phrase_sort_order'),
                        'required' => false,
                        'placeholder' => '0',
                    ])

                    @include('components.form.select-field', [
                        'name' => 'scope',
                        'label' => __('templates.scope'),
                        'required' => true,
                        'options' => [
                            ['value' => 'system', 'text' => __('templates.system')],
                            ['value' => 'personal', 'text' => __('templates.personal')],
                        ],
                    ])

                    @include('components.form.checkbox-field', [
                        'name' => 'is_active',
                        'label' => __('common.status'),
                        'text' => __('common.active'),
                        'value' => '1',
                        'checked' => true,
                    ])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" id="btn-save" class="btn btn-primary" onclick="save_phrase()">{{ __('common.save_record') }}</button>
            </div>
        </div>
    </div>
</div>
