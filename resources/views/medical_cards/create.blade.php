<div class="modal fade" id="card-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('medical.upload_image') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="{{ route('medical-cards.store') }}" method="post" id="card-form" autocomplete="off"
                      enctype="multipart/form-data">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('patient.patient') }}</label>
                        <select id="patient" name="patient_id" class="form-control" style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical.imaging_type') }} </label><br>
                        <input type="radio" name="card_type" value="X-ray"> {{ __('medical.xray') }}
                        <input type="radio" name="card_type" value="Medical Card"> {{ __('patient.medical_card') }}
                    </div>
                    <div class="form-group">
                        <input type="file" id="uploadFile" name="uploadFile[]" multiple/>
                    </div>
                    <div id="image_preview"></div>
                    <br>
                    <input type="submit" id="btn-save" value="{{ __('common.save_changes') }}" class="btn btn-primary">

                </form>

            </div>

        </div>
    </div>
</div>


