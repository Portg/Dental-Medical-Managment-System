<div class="modal fade modal-form" id="patient-history-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{__('patient.patient_history')}}<span class="text-primary patientInfoText"></span></h4>
            </div>
            <div class="modal-body">
                <div id="patientHistoryContainer">

                </div>
                <h3 class="text-primary noResultsText">{{__('patient.patient_no_history_treatment')}}</h3>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{__('common.close')}}</button>
            </div>
        </div>
    </div>
</div>
