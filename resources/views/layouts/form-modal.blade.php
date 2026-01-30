{{--
    Form Modal Base Template
    =========================

    Usage:
    @extends('layouts.form-modal')

    Required sections:
    - modal_id: Unique modal ID (e.g., 'patient-modal')
    - modal_title: Modal title text
    - form_id: Form element ID
    - form_content: Form fields and sections

    Optional sections:
    - modal_size: Modal size class (default: '', options: 'modal-form-sm', 'modal-form-lg')
    - hidden_fields: Hidden form fields
    - footer_buttons: Custom footer buttons (default: Cancel + Save)
    - form_js: Form-specific JavaScript

    Available components:
    - @include('components.form.section') - Collapsible form section
    - @include('components.form.field') - Form field wrapper

    CSS classes available (from form-modal.css):
    - .form-section, .form-section-header, .form-section-body
    - .form-row, .required-asterisk, .field-hint
    - .validation-message, .warning-box, .info-box
    - .conditional-fields (use with .show to display)
--}}

{{-- Modal Container --}}
<div class="modal fade modal-form @yield('modal_size')" id="@yield('modal_id')" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- Modal Header --}}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="@yield('modal_id')-title">@yield('modal_title')</h4>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body">
                {{-- Validation Errors --}}
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>

                {{-- Form --}}
                <form action="#" id="@yield('form_id')" class="form-horizontal" autocomplete="off">
                    @csrf
                    {{-- Hidden Fields --}}
                    @yield('hidden_fields')

                    {{-- Form Content (Sections) --}}
                    @yield('form_content')
                </form>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer">
                @hasSection('footer_buttons')
                    @yield('footer_buttons')
                @else
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" id="btn-save" class="btn btn-primary" onclick="saveForm()">
                        {{ __('common.save') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Base JavaScript --}}
<script>
/**
 * Toggle form section collapse state
 * @param {string} sectionId - The section element ID
 */
function toggleSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var toggle = section.querySelector('.form-section-toggle');
    section.classList.toggle('collapsed');
    if (toggle) {
        toggle.classList.toggle('collapsed');
    }
}

/**
 * Expand a form section
 * @param {string} sectionId - The section element ID
 */
function expandSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var toggle = section.querySelector('.form-section-toggle');
    section.classList.remove('collapsed');
    if (toggle) {
        toggle.classList.remove('collapsed');
    }
}

/**
 * Collapse a form section
 * @param {string} sectionId - The section element ID
 */
function collapseSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var toggle = section.querySelector('.form-section-toggle');
    section.classList.add('collapsed');
    if (toggle) {
        toggle.classList.add('collapsed');
    }
}

/**
 * Show validation error messages
 * @param {object} errors - Error object from server response
 * @param {string} formId - Form element ID
 */
function showValidationErrors(errors, formId) {
    var form = document.getElementById(formId);
    if (!form) return;

    var alertDiv = form.closest('.modal-body').querySelector('.alert-danger');
    if (!alertDiv) return;

    var ul = alertDiv.querySelector('ul');
    ul.innerHTML = '';

    for (var key in errors) {
        if (errors.hasOwnProperty(key)) {
            var messages = errors[key];
            if (Array.isArray(messages)) {
                messages.forEach(function(msg) {
                    var li = document.createElement('li');
                    li.textContent = msg;
                    ul.appendChild(li);
                });
            } else {
                var li = document.createElement('li');
                li.textContent = messages;
                ul.appendChild(li);
            }
        }
    }

    alertDiv.style.display = 'block';
}

/**
 * Hide validation error messages
 * @param {string} formId - Form element ID
 */
function hideValidationErrors(formId) {
    var form = document.getElementById(formId);
    if (!form) return;

    var alertDiv = form.closest('.modal-body').querySelector('.alert-danger');
    if (alertDiv) {
        alertDiv.style.display = 'none';
        var ul = alertDiv.querySelector('ul');
        if (ul) ul.innerHTML = '';
    }
}

/**
 * Reset form to initial state
 * @param {string} formId - Form element ID
 */
function resetForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return;

    form.reset();
    hideValidationErrors(formId);

    // Clear validation states
    var inputs = form.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.classList.remove('is-valid', 'is-invalid');
    });

    // Hide validation messages
    var validationMsgs = form.querySelectorAll('.validation-message');
    validationMsgs.forEach(function(msg) {
        msg.style.display = 'none';
    });

    // Reset Select2 fields
    $(form).find('select.select2, select[data-select2]').each(function() {
        $(this).val(null).trigger('change');
    });
}

/**
 * Set button state during form submission
 * @param {string} buttonId - Button element ID
 * @param {boolean} loading - Whether form is submitting
 * @param {string} loadingText - Text to show while loading
 * @param {string} normalText - Normal button text
 */
function setButtonLoading(buttonId, loading, loadingText, normalText) {
    var btn = document.getElementById(buttonId);
    if (!btn) return;

    btn.disabled = loading;
    btn.textContent = loading ? loadingText : normalText;
}

/**
 * Calculate age from birthday
 * @param {string} birthday - Date string (yyyy-mm-dd)
 * @returns {number|null} - Age in years or null if invalid
 */
function calculateAge(birthday) {
    if (!birthday) return null;
    var today = new Date();
    var birthDate = new Date(birthday);
    var age = today.getFullYear() - birthDate.getFullYear();
    var monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return (age >= 0 && age < 150) ? age : null;
}

/**
 * Validate email format
 * @param {string} email - Email address
 * @returns {boolean} - Whether email is valid
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Validate phone number format (Chinese mobile)
 * @param {string} phone - Phone number
 * @returns {boolean} - Whether phone is valid
 */
function isValidPhone(phone) {
    return /^1[3-9]\d{9}$/.test(phone.replace(/\s/g, ''));
}

/**
 * Parse Chinese ID card to extract birthday and gender
 * @param {string} idCard - 18-digit Chinese ID card number
 * @returns {object|null} - {birthday: 'yyyy-mm-dd', gender: 'Male'|'Female'} or null
 */
function parseChineseIdCard(idCard) {
    if (!idCard || idCard.length !== 18) return null;

    var year = idCard.substring(6, 10);
    var month = idCard.substring(10, 12);
    var day = idCard.substring(12, 14);
    var birthday = year + '-' + month + '-' + day;

    var genderCode = parseInt(idCard.substring(16, 17));
    var gender = (genderCode % 2 === 1) ? 'Male' : 'Female';

    return {
        birthday: birthday,
        gender: gender
    };
}
</script>

{{-- Form-specific JavaScript --}}
@yield('form_js')
