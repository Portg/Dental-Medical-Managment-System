<div class="modal fade" id="claims-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Claims Form </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="claims-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="appointment_id" name="appointment_id">
                    <div class="form-group">
                        <label class="">Treatment Amount</label>
                        <input type="number" class="form-control" placeholder="Enter amount" name="amount"/>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="update_record()">Update changes
                </button>
            </div>
        </div>
    </div>
</div>


