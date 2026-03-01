@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    <link rel="stylesheet" href="{{ asset('css/ocr-recognize.css') }}">
@endsection

@section('content')
<div class="container-fluid ocr-page">
    {{-- Phase 1: Upload --}}
    <div id="upload-section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('ocr.page_title') }}</h4>
            </div>
            <div class="card-body">
                <div id="drop-zone" class="drop-zone">
                    <div class="drop-zone-content">
                        <i class="fa fa-camera fa-3x text-muted"></i>
                        <p class="mt-3">{{ __('ocr.upload_hint') }}</p>
                        <p class="text-muted small">{{ __('ocr.supported_formats') }}</p>
                        <input type="file" id="image-input" accept="image/jpeg,image/png,image/jpg,image/bmp" class="d-none">
                    </div>
                    <div id="image-preview-upload" class="d-none">
                        <img id="preview-img-upload" src="" alt="preview">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="btn-clear-image">
                            <i class="fa fa-times"></i> {{ __('common.remove') }}
                        </button>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-primary btn-lg" id="btn-recognize" disabled>
                        <i class="fa fa-search"></i> {{ __('ocr.start_recognize') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div id="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">{{ __('ocr.recognizing') }}</p>
        </div>
    </div>

    {{-- Phase 2: Results --}}
    <div id="result-section" class="d-none">
        <div class="row">
            {{-- Left: Image preview + raw text --}}
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('ocr.original_image') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <img id="result-image" src="" alt="original" class="img-fluid result-image">
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('ocr.raw_text') }}</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-toggle-raw">
                            <i class="fa fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="card-body d-none" id="raw-text-body">
                        <pre id="raw-text-content" class="raw-text-pre"></pre>
                    </div>
                </div>
                {{-- Confidence indicator --}}
                <div id="confidence-alert" class="alert" role="alert">
                    <i class="fa fa-info-circle"></i>
                    <span id="confidence-text"></span>
                    <span id="confidence-value" class="badge"></span>
                </div>
            </div>

            {{-- Right: Editable form --}}
            <div class="col-md-7">
                <form id="ocr-create-form">
                    @csrf
                    {{-- Patient Info --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('ocr.patient_info') }}</h5>
                        </div>
                        <div class="card-body">
                            {{-- Patient mode toggle --}}
                            <div class="form-group mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="patient_mode" id="mode-new" value="new" checked>
                                    <label class="form-check-label" for="mode-new">{{ __('ocr.create_new_patient') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="patient_mode" id="mode-existing" value="existing">
                                    <label class="form-check-label" for="mode-existing">{{ __('ocr.link_existing_patient') }}</label>
                                </div>
                            </div>

                            {{-- Existing patient search --}}
                            <div id="existing-patient-box" class="form-group mb-3 d-none">
                                <label>{{ __('patient.search') }}</label>
                                <select id="patient-search" class="form-control" name="patient_id" style="width:100%"></select>
                            </div>

                            {{-- New patient fields --}}
                            <div id="new-patient-fields">
                                <div class="row">
                                    <div class="col-md-4 form-group mb-3">
                                        <label>{{ __('patient.full_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="full_name" id="field-full_name">
                                    </div>
                                    <div class="col-md-4 form-group mb-3">
                                        <label>{{ __('patient.gender') }} <span class="text-danger">*</span></label>
                                        <select class="form-control" name="gender" id="field-gender">
                                            <option value="">{{ __('common.select') }}</option>
                                            <option value="Male">{{ __('patient.male') }}</option>
                                            <option value="Female">{{ __('patient.female') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group mb-3">
                                        <label>{{ __('patient.age') }}</label>
                                        <input type="text" class="form-control" name="age" id="field-age">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 form-group mb-3">
                                        <label>{{ __('patient.phone_no') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="telephone" id="field-phone_no">
                                    </div>
                                    <div class="col-md-4 form-group mb-3">
                                        <label>{{ __('patient.date_of_birth') }}</label>
                                        <input type="text" class="form-control datepicker" name="dob" id="field-dob" autocomplete="off">
                                    </div>
                                    <div class="col-md-4 form-group mb-3">
                                        <label>{{ __('patient.blood_type') }}</label>
                                        <select class="form-control" name="blood_type" id="field-blood_type">
                                            <option value="">{{ __('common.select') }}</option>
                                            @foreach(\App\Patient::$bloodTypeOptions as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label>{{ __('patient.address') }}</label>
                                        <input type="text" class="form-control" name="address" id="field-address">
                                    </div>
                                    <div class="col-md-6 form-group mb-3">
                                        <label>{{ __('patient.nin') }}</label>
                                        <input type="text" class="form-control" name="nin" id="field-nin">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label>{{ __('patient.drug_allergy') }}</label>
                                    <input type="text" class="form-control" name="drug_allergies_other" id="field-drug_allergies_other">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Case Info (SOAP) --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('ocr.case_info') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label>{{ __('ocr.case_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control datepicker" name="case_date" id="field-case_date" autocomplete="off">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label>{{ __('ocr.attending_doctor') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="doctor_id" id="field-doctor_id">
                                        <option value="">{{ __('common.select') }}</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">{{ $doctor->surname }}{{ $doctor->othername }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.chief_complaint') }}</label>
                                <textarea class="form-control" name="chief_complaint" id="field-chief_complaint" rows="2"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.history_of_present_illness') }}</label>
                                <textarea class="form-control" name="history_of_present_illness" id="field-history_of_present_illness" rows="3"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.examination') }}</label>
                                <textarea class="form-control" name="examination" id="field-examination" rows="3"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.auxiliary_examination') }}</label>
                                <textarea class="form-control" name="auxiliary_examination" id="field-auxiliary_examination" rows="2"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.diagnosis') }}</label>
                                <textarea class="form-control" name="diagnosis" id="field-diagnosis" rows="2"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.treatment') }}</label>
                                <textarea class="form-control" name="treatment" id="field-treatment" rows="3"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('ocr.medical_orders') }}</label>
                                <textarea class="form-control" name="medical_orders" id="field-medical_orders" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="text-center mb-4">
                        <button type="button" class="btn btn-secondary mr-2" id="btn-back-upload">
                            <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="btn-create">
                            <i class="fa fa-check"></i> <span id="btn-create-text">{{ __('ocr.create_patient_and_case') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    LanguageManager.loadAllFromPHP({
        'ocr': @json(__('ocr'))
    });

    var searchPatientUrl = '{{ url("search-patient") }}';
    var recognizeUrl = '{{ url("ocr-recognize/recognize") }}';
    var createUrl = '{{ url("ocr-recognize/create") }}';
    var patientShowUrl = '{{ url("patients") }}';
</script>
<script src="{{ asset('include_js/ocr_recognize.js') }}?v={{ filemtime(public_path('include_js/ocr_recognize.js')) }}" type="text/javascript"></script>
@endsection
