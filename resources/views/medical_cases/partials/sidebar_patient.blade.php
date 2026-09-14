{{-- 患者选择卡。

     姓名 / 性别年龄 / 电话 / 过敏史现在都印在病历纸上（edit.blade.php 的
     .mr-identity），这里不再重复显示 —— 那几个 id 是 enableFormWithPatient()
     写入的目标，同一个 id 在页面上出现两次，jQuery 只认得到第一个，
     另一处就永远是空的（这套代码栽过一次，见 46287dc）。

     所以这张卡只剩它真正的职责：还没选患者时把患者选出来，选完了给一个
     换人的入口。患者已经确定的情况（编辑既有病历、从工作台带患者进来）
     整张卡都不渲染 —— 纸上写着是谁，侧栏再说一遍是多余的。 --}}
@if($needPatientSelection)
<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.patient_info') }}</span>
        </div>
    </div>
    <div class="portlet-body">
        {{-- Patient Selector (for create mode without patient) --}}
        <div id="patient-selector-section">
            <select name="patient_selector" id="patient_selector" class="form-control select2" style="width: 100%;">
                <option value=""></option>
            </select>
        </div>

        {{-- 选中之后换成「头像 + 更换」。姓名与性别年龄看纸面。 --}}
        <div id="selected-patient-info" style="display: none;">
            <div class="row">
                <div class="col-xs-3">
                    <div class="patient-avatar-large" id="patient-avatar">-</div>
                </div>
                <div class="col-xs-9 text-right">
                    <button type="button" class="btn btn-xs btn-default" onclick="changePatient()">
                        {{ __('common.change') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
