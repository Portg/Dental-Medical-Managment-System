{{-- Service Selector Modal --}}
<div class="modal fade" id="service_selector_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('medical_cases.add_service') }}</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('medical_cases.treatment_services') }}</label>
                    <select id="service_search_select" class="form-control" style="width: 100%;">
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="confirmServiceSelection()">
                    {{ __('common.confirm') }}
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#service_selector_modal').on('shown.bs.modal', function() {
        $('#service_search_select').select2({
            dropdownParent: $('#service_selector_modal'),
            ajax: {
                url: '{{ url("search-medical-service") }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            placeholder: '{{ __("common.type_to_search") }}',
            minimumInputLength: 0,
            allowClear: true,
            templateResult: function(item) {
                if (!item.id) return item.text;
                var price = item.price ? (' - ¥' + parseFloat(item.price).toFixed(2)) : '';
                return $('<span>' + item.text + '<small style="color:#999;">' + price + '</small></span>');
            }
        });
    });

    $('#service_selector_modal').on('hidden.bs.modal', function() {
        $('#service_search_select').val(null).trigger('change');
    });
});

function confirmServiceSelection() {
    var $select = $('#service_search_select');
    var selected = $select.select2('data');
    if (!selected || selected.length === 0 || !selected[0].id) {
        return;
    }

    var item = selected[0];
    var serviceId = item.id;
    var serviceName = item.text;

    // Check if already added
    var services = JSON.parse($('#treatment_services').val() || '[]');
    var exists = services.some(function(s) { return s.id == serviceId; });
    if (exists) {
        toastr.warning('{{ __("common.already_exists") }}');
        return;
    }

    // Add to list
    services.push({ id: serviceId, name: serviceName });
    $('#treatment_services').val(JSON.stringify(services));

    // Add tag UI
    var tag = '<span class="service-tag" data-id="' + serviceId + '">' +
              serviceName +
              '<span class="remove-service" onclick="removeService(\'' + serviceId + '\')">&times;</span>' +
              '</span>';
    $('#treatment-service-tags').find('.add-teeth-btn').before(tag);

    $('#service_selector_modal').modal('hide');
}
</script>
