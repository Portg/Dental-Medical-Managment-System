/**
 * English translation file
 */

const en = {
    // Common text
    common: {
        hello: 'Hello',
        welcome: 'Welcome',
        save: 'Save',
        cancel: 'Cancel',
        delete: 'Delete',
        edit: 'Edit',
        search: 'Search',
        loading: 'Loading...',
        confirm: 'Confirm',
        back: 'Back',
        next: 'Next',
        submit: 'Submit',
        close: 'Close'
    },

    // User related
    user: {
        login: 'Login',
        logout: 'Logout',
        register: 'Register',
        profile: 'Profile',
        username: 'Username',
        password: 'Password',
        email: 'Email',
        phone: 'Phone',
        greeting: 'Hello, {name}!',
        welcome_message: 'Welcome back, {name}! You have been offline for {days} days.'
    },

    // Dental/Medical related
    dental: {
        appointment: 'Appointment',
        patient: 'Patient',
        doctor: 'Doctor',
        treatment: 'Treatment',
        diagnosis: 'Diagnosis',
        prescription: 'Prescription',
        medical_history: 'Medical History',
        tooth_number: 'Tooth Number',
        treatment_plan: 'Treatment Plan'
    },

    // Appointment management
    appointment: {
        title: 'Appointment Management',
        new_appointment: 'New Appointment',
        view_appointment: 'View Appointment',
        edit_appointment: 'Edit Appointment',
        cancel_appointment: 'Cancel Appointment',
        appointment_date: 'Appointment Date',
        appointment_time: 'Appointment Time',
        status: {
            pending: 'Pending',
            confirmed: 'Confirmed',
            completed: 'Completed',
            cancelled: 'Cancelled'
        }
    },

    // Form validation
    validation: {
        required: 'This field is required',
        email_invalid: 'Please enter a valid email address',
        password_min_length: 'Password must be at least {min} characters',
        phone_invalid: 'Please enter a valid phone number',
        date_invalid: 'Please enter a valid date'
    },

    // Messages
    message: {
        success: 'Operation successful!',
        error: 'Operation failed, please try again.',
        save_success: 'Saved successfully!',
        delete_success: 'Deleted successfully!',
        delete_confirm: 'Are you sure you want to delete? This action cannot be undone.',
        network_error: 'Network error, please check your connection.',
        session_expired: 'Session expired, please login again.'
    },

    // Plural forms example
    items: {
        patient_count: 'no patients | {count} patient | {count} patients',
        appointment_count: 'no appointments | {count} appointment | {count} appointments',
        notification_count: 'no new notifications | {count} new notification | {count} new notifications'
    },

    // Date and time
    datetime: {
        today: 'Today',
        yesterday: 'Yesterday',
        tomorrow: 'Tomorrow',
        this_week: 'This Week',
        last_week: 'Last Week',
        this_month: 'This Month',
        last_month: 'Last Month',
        morning: 'Morning',
        afternoon: 'Afternoon',
        evening: 'Evening'
    }
};

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = en;
}
if (typeof window !== 'undefined') {
    window.i18nMessages = window.i18nMessages || {};
    window.i18nMessages['en'] = en;
}
