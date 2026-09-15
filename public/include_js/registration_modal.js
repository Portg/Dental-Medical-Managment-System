/**
 * 挂号弹窗（resources/views/waiting_queue/partials/register_modal.blade.php）的脚本。
 *
 * 挂号 = 到店患者当场建今天的就诊并进候诊队列。前台的两条高频动线都走这里：
 *   - 新患者建档保存后：直接带着新患者弹出来（视频里保存患者后就是这个窗口）
 *   - 老患者到店：患者列表行操作里的「挂号」，或工作台顶部的「挂号」按钮
 *
 * 语言包由宿主页面注入（today_work / common），这里一律走 LanguageManager.trans。
 */
(function () {
    'use strict';

    // 工作台有自己的 config，患者列表没有 —— 退回 <html lang>，那是布局一直在写的
    var locale = (window.TodayWorkIndexConfig && TodayWorkIndexConfig.locale)
        || document.documentElement.lang || 'zh-CN';

    function t(key) {
        return LanguageManager.trans('today_work.' + key);
    }

    /**
     * 打开挂号弹窗。
     *
     * @param {Object} prefill {patient_id, patient_name, doctor_id, doctor_name, appointment_type}
     *        新建患者过来时带 patient_id + patient_name —— 那位患者还没进过任何列表，
     *        select2 搜不出来，得把 option 现造一个塞进去。
     * @returns {boolean} 有没有真的弹出来。
     *        调用方要靠这个值决定还要不要自己给一句反馈：这个函数在没有建预约
     *        权限的页面上是弹不出来的，而本文件总是被加载，光靠 typeof 判断
     *        不出来（函数在，弹窗不在）。
     */
    window.openRegistrationModal = function (prefill) {
        prefill = prefill || {};

        // 没有建预约权限的页面不会渲染这个弹窗（blade 里 @can 包着）。
        // 新建患者保存后会自动调这里，所以要挡一下，别报个 undefined 了事。
        if (!$('#registration-modal').length) {
            return false;
        }

        $('#registration-form')[0].reset();
        setSelection('#reg_patient_id', prefill.patient_id, prefill.patient_name);
        setSelection('#reg_doctor_id', prefill.doctor_id, prefill.doctor_name);
        setSelection('#reg_service_id', null, null);
        $('#reg_notes').val(prefill.notes || '');

        var visitType = prefill.appointment_type || 'first_visit';
        $('input[name="appointment_type"][value="' + visitType + '"]').prop('checked', true);

        $('#registration-modal').modal('show');
        return true;
    };

    /** 给 select2 塞一个「库里还没被搜到过」的选项并选中；值为空时清空 */
    function setSelection(selector, id, text) {
        var $el = $(selector);
        $el.empty();
        if (id) {
            $el.append(new Option(text || id, id, true, true));
        }
        $el.trigger('change');
    }

    window.submitRegistration = function () {
        var patientId = $('#reg_patient_id').val();
        var doctorId = $('#reg_doctor_id').val();

        if (!patientId) {
            toastr.error(t('register_no_patient'));
            return;
        }
        if (!doctorId) {
            toastr.error(t('register_no_doctor'));
            return;
        }

        $('#btn-register-submit').attr('disabled', true);
        $.ajax({
            type: 'POST',
            url: '/waiting-queue/register',
            data: {
                _token: (window.csrfToken || $('meta[name="csrf-token"]').attr('content')),
                patient_id: patientId,
                doctor_id: doctorId,
                appointment_type: $('input[name="appointment_type"]:checked').val(),
                service_id: $('#reg_service_id').val() || null,
                notes: $('#reg_notes').val() || null
            },
            success: function (resp) {
                $('#btn-register-submit').attr('disabled', false);
                toastr.success((resp && resp.message) || t('register_success'));
                $('#registration-modal').modal('hide');

                // 工作台上挂完号，患者应该当场出现在今日就诊里；
                // 在患者列表页挂号则没有这张表，什么都不用做。
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tw-table')) {
                    $('#tw-table').DataTable().ajax.reload(null, false);
                }
                if (typeof refreshStats === 'function') {
                    refreshStats();
                }
            },
            error: function (request) {
                $('#btn-register-submit').attr('disabled', false);
                var json = request.responseJSON || {};
                if (json.errors) {
                    $.each(json.errors, function (key, value) {
                        toastr.error(value[0] || value);
                    });
                } else {
                    toastr.error(json.message || LanguageManager.trans('common.error_message'));
                }
            }
        });
    };

    $(document).ready(function () {
        var dropdownParent = $('#registration-modal');

        $('#reg_patient_id').select2({
            language: locale,
            placeholder: t('register_patient'),
            allowClear: true,
            minimumInputLength: 2,
            dropdownParent: dropdownParent,
            ajax: {
                url: '/search-patient',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term, full: 1 }; },
                processResults: function (data) {
                    return {
                        results: data.map(function (item) {
                            var phone = item.phone_no ? item.phone_no.slice(-4) : '';
                            return {
                                id: item.id,
                                text: LanguageManager.joinName(item.surname, item.othername) + (phone ? ' ***' + phone : '')
                            };
                        })
                    };
                }
            }
        });

        $('#reg_doctor_id').select2({
            language: locale,
            placeholder: t('register_doctor'),
            allowClear: true,
            dropdownParent: dropdownParent,
            ajax: {
                url: '/search-doctor',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; }
            }
        });

        $('#reg_service_id').select2({
            language: locale,
            placeholder: t('register_service'),
            allowClear: true,
            dropdownParent: dropdownParent,
            ajax: {
                url: '/search-medical-service',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; }
            }
        });

        // 顶栏搜索「挂号」在无弹窗页会跳到 ?register_patient_id=；
        // 凡加载了本弹窗的页面（工作台/患者列表）到站后自动打开。
        var qs = new URLSearchParams(window.location.search);
        var registerPid = qs.get('register_patient_id');
        if (registerPid) {
            window.openRegistrationModal({
                patient_id: registerPid,
                patient_name: qs.get('register_patient_name') || registerPid
            });
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('register_patient_id');
                url.searchParams.delete('register_patient_name');
                window.history.replaceState({}, '', url.pathname + url.search + url.hash);
            }
        }
    });
})();
