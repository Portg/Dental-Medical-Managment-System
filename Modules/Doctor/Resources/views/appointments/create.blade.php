<div class="modal fade" id="appointment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> Appointment Form </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="appointment-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">Patient</label>
                        <select id="patient" name="patient_id" class="form-control" style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">General Notes(Optional) </label>
                        <textarea class="form-control" rows="10" name="notes"
                                  placeholder="Enter general notes here (if any)"></textarea>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">Save changes</button>
            </div>
        </div>
    </div>
</div>


