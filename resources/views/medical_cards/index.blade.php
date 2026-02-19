@extends('layouts.list-page')

@section('page_title', __('medical_cards.cards'))
@section('table_id', 'sample_1')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.date') }}</th>
    <th>{{ __('medical_cards.patient') }}</th>
    <th>{{ __('medical_cards.card_type') }}</th>
    <th>{{ __('medical_cards.added_by') }}</th>
    <th>{{ __('medical_cards.view_cards') }}</th>
    <th>{{ __('common.delete') }}</th>
    <th><button type="button" name="bulk_delete" id="bulk_delete" class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-remove"></i></button></th>
@endsection

@section('modals')
    @include('medical_cards.create')
@endsection

@section('page_js')
    <style type="text/css">
        input[type=file] {
            display: inline;
        }

        #image_preview {
            border: 1px solid black;
            padding: 10px;
        }

        #image_preview img {
            width: 200px;
            padding: 5px;
        }
    </style>
    <script type="text/javascript">
        $(function () {

            // 批量加载
            LanguageManager.loadAllFromPHP({
                'patient': @json(__('patient')),
                'medical': @json(__('medical'))
            });
            dataTable = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language : LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/medical-cards/') }}",
                    data: function (d) {
                        d.search = $('input[type="search"]').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'patient', name: 'patient'},
                    {data: 'card_type', name: 'card_type'},
                    {data: 'added_by', name: 'added_by'},
                    {data: 'view_cards', name: 'view_cards'},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false},
                    { "data":"checkbox", orderable:false, searchable:false}
                ]
            });

            setupEmptyStateHandler();
        });


        function createRecord() {
            $("#card-form")[0].reset();
            $('#card-modal').modal('show');
        }


        //filter patients
        $('#patient').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{__('patient.choose_patient')}}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-patient',
                dataType: 'json',
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        function save_data() {
            //check save method
            var id = $('#id').val();
            if (id === "") {
                save_new_record();
            } else {
                update_record();
            }
        }

        function save_new_record() {
           $.LoadingOverlay("show");
            $('#btnSave').attr('disabled', true);
            $('#btnSave').text('{{ __("common.processing") }}');
            let form = $('#card-form')[0];
            let formData = new FormData(form);

            $.ajax({
                type: 'POST',
                url: "{{ url('medical-cards')}}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: (data) => {
                    $('#card-modal').modal('hide');
                   $.LoadingOverlay("hide");
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                },
                error: function (request, status, error) {
                   $.LoadingOverlay("hide");
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });

        }

        function editRecord(id) {
           $.LoadingOverlay("show");
            $.ajax({
                type: 'get',
                url: "medical-cards/" + id + "/edit",
                success: function (data) {
                    $('#id').val(id);
                    $('[name="name"]').val(data.name);
                    let patient_data = {
                        id: data.patient_id,
                        text: LanguageManager.joinName(data.surname, data.othername)
                    };
                    let newOption = new Option(patient_data.text, patient_data.id, true, true);
                    $('#patient').append(newOption).trigger('change');

                   $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}')
                    $('#card-modal').modal('show');

                },
                error: function (request, status, error) {
                   $.LoadingOverlay("hide");
                }
            });
        }

        function update_record() {
           $.LoadingOverlay("show");

            $('#btnSave').attr('disabled', true);
            $('#btnSave').text('{{ __("common.updating") }}');
            $.ajax({
                type: 'PUT',
                data: $('#category-form').serialize(),
                url: "/expense-categories/" + $('#id').val(),
                success: function (data) {
                    $('#category-modal').modal('hide');
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                   $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                   $.LoadingOverlay("hide");
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function deleteRecord(id) {
            swal({
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('medical_cards.cannot_recover_card') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "{{ __('common.yes_delete_it') }}",
                    closeOnConfirm: false
                },
                function () {
                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'delete',
                        data: {
                            _token: CSRF_TOKEN
                        },
                        url: "/medical-cards/" + id,
                        success: function (data) {
                            if (data.status) {
                                alert_dialog(data.message, "success");
                            } else {
                                alert_dialog(data.message, "danger");
                            }
                           $.LoadingOverlay("hide");
                        },
                        error: function (request, status, error) {
                           $.LoadingOverlay("hide");

                        }
                    });

                });

        }


        $("#uploadFile").change(function () {
            var total_file = document.getElementById("uploadFile").files.length;

            for (var i = 0; i < total_file; i++) {
                $('#image_preview').append("<img src='" + URL.createObjectURL(event.target.files[i]) + "'>");
            }

        });


        $(document).on('click', '#bulk_delete', function(){
            var id = [];
            if(confirm("{{ __('common.are_you_sure') }}"))
            {
                $('.student_checkbox:checked').each(function(){
                    id.push($(this).val());
                });
                if(id.length > 0)
                {
                    $.ajax({
                        url:"{{ url('ajaxdata.massremove')}}",
                        method:"get",
                        data:{id:id},
                        success:function(data)
                        {
                            alert(data);
                            $('#student_table').DataTable().ajax.reload();
                        }
                    });
                }
                else
                {
                    alert("{{ __('common.please_select_checkbox') }}");
                }
            }
        });


    </script>
@endsection
