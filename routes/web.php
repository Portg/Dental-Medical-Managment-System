<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/book-appointment', 'OnlineBookingController@frontend');
Route::post('request-appointment', 'OnlineBookingController@store');

Route::group(['middleware' => ['auth']], function () {

    Route::get('/home', 'HomeController@index')->name('home');

    // get the todays report
    Route::get('todays-cash', 'InvoicingReportsController@todaysCash');
    Route::get('todays-insurance', 'InvoicingReportsController@todaysInsurance');

    Route::get('todays-expenses', 'InvoicingReportsController@todaysExpenses');
    Route::resource('roles', 'RoleController');
    Route::get('search-role', 'RoleController@filterRoles');
    Route::resource('users', 'UsersController');
    //current user profile
    Route::get('/profile', 'ProfileController@index');
    Route::post('update-bio', 'ProfileController@update_Bio');
    Route::post('update-avatar', 'ProfileController@update_Avatar');
    Route::post('update-password', 'ProfileController@changePassword');

    Route::get('search-doctor', 'UsersController@filterDoctor');
    // API endpoints for appointment form (design spec F-APT-001)
    Route::get('api/chairs', 'AppointmentsController@getChairs');
    Route::get('api/doctor-time-slots', 'AppointmentsController@getDoctorTimeSlots');
    Route::get('search-medical-service', 'MedicalServiceController@filterServices');
    Route::resource('insurance-companies', 'InsuranceCompaniesController');
    Route::get('search-insurance-company', 'InsuranceCompaniesController@filterCompanies');
    //self accounts
    Route::resource('self-accounts', 'SelfAccountController');

    Route::get('search-self-account', 'SelfAccountController@filterAccounts');

    Route::get('self-account-deposits/{self_account_id}', 'SelfAccountDepositController@index');
    Route::resource('self-account-deposits', 'SelfAccountDepositController');

    //self account bills/invoices
    Route::get('self-account-bills/{self_account_id}', 'SelfAccountBillPayment@index');


    Route::resource('patients', 'PatientController');
    Route::get('patients/{patientId}/medicalHistory', 'PatientController@patientMedicalHistory');

    Route::get('export-patients', 'PatientController@exportPatients');
    Route::get('search-patient', 'PatientController@filterPatients');
    Route::resource('appointments', 'AppointmentsController');
    Route::post('appointments-reschedule', 'AppointmentsController@sendReschedule');

    Route::get('export-appointments', 'AppointmentsController@exportAppointmentReport');

    Route::resource('online-bookings', 'OnlineBookingController');

    Route::get('medical-history/{id}', 'MedicalHistoryController@index');

    Route::get('surgeries/{id}', 'SurgeriesController@index');
    Route::resource('surgeries', 'SurgeriesController');

    Route::get('chronic-diseases/{id}', 'ChronicDiseasesController@index');
    Route::resource('chronic-diseases', 'ChronicDiseasesController');

    Route::get('allergies/{patient_id}', 'AllergyController@index');
    Route::resource('allergies', 'AllergyController');

    Route::resource('medical-cards', 'MedicalCardController');
    Route::resource('medical-cards-items', 'MedicalCardItemController');
    Route::get('individual-medical-cards/{id}', 'MedicalCardController@individualMedicalCards');

    Route::resource('clinic-services', 'MedicalServiceController');

    Route::get('search-medical-service', 'MedicalServiceController@filterServices');
    Route::get('services-array', 'MedicalServiceController@servicesArray');

    Route::resource('invoices', 'InvoiceController');
    Route::get('patient-invoices/{patient_id}', 'InvoiceController@patientInvoices');
    Route::get('export-invoices-report', 'InvoiceController@exportReport');
    Route::get('invoices-preview/{id}', 'InvoiceController@previewInvoice');
    Route::get('invoice-amount/{id}', 'InvoiceController@invoiceAmount');
    Route::get('invoice-procedures/{invoice_id}','InvoiceController@invoiceProceduresToJson');

    //share invoice on email
    Route::get('share-invoice-details/{id}', 'InvoiceController@invoiceShareDetails');
    Route::post('share-invoice', 'InvoiceController@sendInvoice');

    Route::get('payments/{id}', 'InvoicePaymentController@index');
    Route::resource('payments', 'InvoicePaymentController');
    Route::post('payments/mixed', 'InvoicePaymentController@storeMixed');
    Route::get('payments/methods', 'InvoicePaymentController@getPaymentMethods');
    Route::post('payments/calculate-change', 'InvoicePaymentController@calculateChange');
    Route::get('print-receipt/{id}', 'InvoiceController@printReceipt');

    // Invoice search API (for refund page)
    Route::get('api/invoices/search', 'InvoiceController@searchInvoices');

    Route::get('invoice-items/{id}', 'InvoiceItemController@index');
    Route::resource('invoice-items', 'InvoiceItemController');
    //doctor invoicing items
    Route::get('appointment-invoice-items/{id}', 'InvoiceItemController@appointmentInvoiceItems');

    // Refunds (PRD 4.1.4)
    Route::get('refunds/pending-approvals', 'RefundController@pendingApprovals');
    Route::get('refunds/pending-count', function() {
        return response()->json(['count' => \App\Refund::pending()->count()]);
    });
    Route::get('refunds/refundable-amount/{invoice_id}', 'RefundController@getRefundableAmount');
    Route::post('refunds/{id}/approve', 'RefundController@approve');
    Route::post('refunds/{id}/reject', 'RefundController@reject');
    Route::get('refunds/{id}/print', 'RefundController@print');
    Route::resource('refunds', 'RefundController');

    // Discount Approval (PRD 4.1.2 BR-035)
    Route::get('invoices/pending-discount-approvals', 'InvoiceController@pendingDiscountApprovals');
    Route::post('invoices/{id}/approve-discount', 'InvoiceController@approveDiscount');
    Route::post('invoices/{id}/reject-discount', 'InvoiceController@rejectDiscount');

    // Credit/Deferred Payment (PRD 4.1.3)
    Route::post('invoices/{id}/set-credit', 'InvoiceController@setCredit');

    // Coupons
    Route::resource('coupons', 'CouponController');
    Route::get('coupons/validate/{code}', 'CouponController@validateCoupon');

    //quotations
    Route::resource('quotations', 'QuotationController');
    Route::get('quotation-items/{id}', 'QuotationItemController@index');
    Route::resource('quotation-items', 'QuotationItemController');
    Route::get('print-quotation/{id}', 'QuotationController@printQuotation');
    //sharing Quotation
    Route::get('share-quotation-details/{id}', 'QuotationController@quotationShareDetails');
    Route::post('share-quotation', 'QuotationController@sendQuotation');


    //sent out billing email notifications
    Route::get('billing-notifications', 'BillingEmailNotificationController@index');


    //listing treatment and prescriptions capture
    Route::get('medical-treatment/{id}', 'MedicalTreatmentController@index');

    Route::get('treatments/{patient_id}', 'TreatmentController@index');
    Route::resource('treatments', 'TreatmentController');
    Route::get('treatments-history/{id}', 'TreatmentController@treatmentHistory');

    Route::get('prescriptions', 'PrescriptionController@listAll');
    Route::get('prescriptions/appointment/{id}', 'PrescriptionController@index');
    Route::resource('prescriptions', 'PrescriptionController')->except(['index']);
    //filter existing drugs
    Route::get('filter-drugs', 'PrescriptionController@filterDrugs')->name('filter-drugs');
    Route::get('print-prescription/{id}', 'PrescriptionController@printPrescription');
    //expenses

    Route::resource('expense-categories', 'ExpenseCategoryController');
    Route::get('expense-categories-array', 'ExpenseCategoryController@filterExpenseCategories'); //populate expense
    // categories array

    Route::get('search-expense-category', 'ExpenseCategoryController@searchCategory');
    Route::resource('expenses', 'ExpenseController');
    Route::get('export-expenses', 'ExpenseController@exportReport');
    Route::resource('suppliers', 'SupplierController');
    Route::get('filter-suppliers', 'SupplierController@filterSuppliers');

    Route::get('expense-items/{expense_id}', 'ExpenseItemController@index');
    Route::resource('expense-items', 'ExpenseItemController');

    Route::get('expense-payments/{expense_id}', 'ExpensePaymentController@index');
    Route::get('purchase-balance/{id}', 'ExpensePaymentController@supplier_balance');
    Route::resource('expense-payments', 'ExpensePaymentController');

    //reports
    Route::get('invoice-payments-report', 'InvoicingReportsController@invoicePaymentReport');
    Route::get('export-invoice-payments-report', 'InvoicingReportsController@exportInvoicePayments');
    Route::get('insurance-reports', 'InsuranceReportsController@index');
    Route::get('insurance-claims', 'InsuranceReportsController@claims');


    Route::get('doctor-performance-report', 'DoctorPerformanceReport@index');
    Route::get('download-performance-report', 'DoctorPerformanceReport@downloadPerformanceReport');

    Route::get('procedure-income-report', 'ProceduresReportController@index');
    Route::get('export-procedure-sales-report', 'ProceduresReportController@downloadProcedureSalesReport');

    Route::get('budget-line-report', 'BudgetLineReportController@index');
    Route::get('export-budget-line', 'BudgetLineReportController@exportBudgetLineReport');

    //end reports


    Route::resource('dental-charting', 'DentalChartController');
    //payroll management
    Route::resource('employee-contracts', 'EmployeeContractController');
    Route::get('search-employee', 'UsersController@filterEmployees');
    Route::resource('salary-advances', 'SalaryAdvanceController');
    Route::resource('payslips', 'PaySlipController');
    Route::get('individual-payslips', 'PaySlipController@individualPaySlip');

    Route::get('allowances/{payslip_id}', 'SalaryAllowanceController@index');
    Route::resource('allowances', 'SalaryAllowanceController');

    Route::get('deductions/{payslip_id}', 'SalaryDeductionController@index');
    Route::resource('deductions', 'SalaryDeductionController');

    //doctor claims
    Route::resource('claim-rates', 'ClaimRateController');
    Route::resource('doctor-claims', 'DoctorClaimController');

    Route::get('claims-payment/{claim_id}', 'DoctorClaimPaymentController@index');
    Route::resource('claims-payment', 'DoctorClaimPaymentController');

    Route::resource('branches', 'BranchController');
    Route::get('search-branch', 'BranchController@filterBranches');

    //leave mgt
    Route::resource('holidays', 'HolidayController');
    Route::resource('leave-types', 'LeaveTypeController');
    Route::get('search-leave-type', 'LeaveTypeController@filter'); //search leave type
    Route::get('get-all-leave-types', 'LeaveTypeController@getAll'); //get all leave types for dropdown
    Route::resource('leave-requests', 'LeaveRequestController');
    //leave approval
    Route::get('leave-requests-approval', 'LeaveRequestApprovalController@index');
    Route::get('approve-leave-request/{id}', 'LeaveRequestApprovalController@approveRequest');
    Route::get('reject-leave-request/{id}', 'LeaveRequestApprovalController@rejectRequest');

    //sms manager
    Route::resource('outbox-sms', 'SmsLoggingController');
    Route::get('export-sms-report', 'SmsLoggingController@exportReport');
    Route::resource('sms-transactions', 'SmsTransactionController');
    Route::resource('birthday-wishes', 'BirthDayMessageController');


    //accounting
    Route::get('charts-of-accounts', 'AccountingEquationController@index');
    Route::resource('charts-of-accounts-items', 'ChartOfAccountItemController');

    Route::resource('permissions', 'PermissionController');
    Route::resource('role-permissions', 'RolePermissionController');
    Route::get('/backup', function () {
        \Illuminate\Support\Facades\Artisan::call('backup:run');
        return 'Successful backup!';
    });

    //debtors report
    Route::get('/debtors', 'DebtorsReportController@index');
    Route::get('/debtors-export', 'DebtorsReportController@exportReport');

    // Medical Cases System
    Route::resource('medical-cases', 'MedicalCaseController');
    Route::get('patient-medical-cases/{patient_id}', 'MedicalCaseController@patientCases');
    Route::get('medical-case-new/{patient_id}', 'MedicalCaseController@createForPatient');
    Route::get('api/medical-case/{id}', 'MedicalCaseController@getCase');
    Route::get('api/icd10-codes', 'MedicalCaseController@searchIcd10');
    Route::get('print-medical-case/{id}', 'MedicalCaseController@printCase');

    // Diagnoses
    Route::get('diagnoses/{patient_id}', 'DiagnosisController@index');
    Route::get('case-diagnoses/{case_id}', 'DiagnosisController@caseIndex');
    Route::resource('diagnoses', 'DiagnosisController')->except(['index']);

    // Progress Notes (SOAP)
    Route::get('progress-notes/{patient_id}', 'ProgressNoteController@index');
    Route::get('case-progress-notes/{case_id}', 'ProgressNoteController@caseIndex');
    Route::resource('progress-notes', 'ProgressNoteController')->except(['index']);

    // Treatment Plans
    Route::get('treatment-plans', 'TreatmentPlanController@listAll');
    Route::get('treatment-plans/patient/{patient_id}', 'TreatmentPlanController@index');
    Route::get('case-treatment-plans/{case_id}', 'TreatmentPlanController@caseIndex');
    Route::resource('treatment-plans', 'TreatmentPlanController')->except(['index']);

    // Vital Signs
    Route::get('vital-signs/{patient_id}', 'VitalSignController@index');
    Route::get('case-vital-signs/{case_id}', 'VitalSignController@caseIndex');
    Route::get('latest-vital-signs/{patient_id}', 'VitalSignController@latest');
    Route::resource('vital-signs', 'VitalSignController')->except(['index']);

    // Patient Images
    Route::get('patient-images', 'PatientImageController@index');
    Route::get('patient-images/{patient_id}/list', 'PatientImageController@patientImages');
    Route::resource('patient-images', 'PatientImageController')->except(['index']);

    // Patient Followups
    Route::get('patient-followups', 'PatientFollowupController@index');
    Route::get('patient-followups/{patient_id}/list', 'PatientFollowupController@patientFollowups');
    Route::get('pending-followups', 'PatientFollowupController@pendingFollowups');
    Route::get('overdue-followups', 'PatientFollowupController@overdueFollowups');
    Route::post('patient-followups/{id}/complete', 'PatientFollowupController@complete');
    Route::resource('patient-followups', 'PatientFollowupController')->except(['index']);

    // Members Management
    Route::resource('members', 'MemberController');
    Route::get('members/{id}/transactions', 'MemberController@transactions');
    Route::post('members/{id}/deposit', 'MemberController@deposit');
    Route::get('member-levels', 'MemberController@levels');
    Route::post('member-levels', 'MemberController@storeLevel');
    Route::get('member-levels/{id}/edit', 'MemberController@editLevel');
    Route::put('member-levels/{id}', 'MemberController@updateLevel');
    Route::delete('member-levels/{id}', 'MemberController@destroyLevel');

    // Language switching
    Route::get('/language/{locale}', 'LanguageController@switch')->name('language.switch');

    // Medical Templates
    Route::resource('medical-templates', 'MedicalTemplateController');
    Route::get('medical-templates-search', 'MedicalTemplateController@search');
    Route::post('medical-templates/{id}/increment-usage', 'MedicalTemplateController@incrementUsage');

    // Quick Phrases
    Route::resource('quick-phrases', 'QuickPhraseController');
    Route::get('quick-phrases-search', 'QuickPhraseController@search');

    // Patient Tags
    Route::resource('patient-tags', 'PatientTagController');
    Route::get('patient-tags-list', 'PatientTagController@list');

    // Patient Sources
    Route::resource('patient-sources', 'PatientSourceController');
    Route::get('patient-sources-list', 'PatientSourceController@list');

    // ============================================================
    // Inventory Management System
    // ============================================================

    // Inventory Categories
    Route::resource('inventory-categories', 'InventoryCategoryController');
    Route::get('inventory-categories-list', 'InventoryCategoryController@list');

    // Inventory Items
    Route::resource('inventory-items', 'InventoryItemController');
    Route::get('inventory-items-search', 'InventoryItemController@search');
    Route::get('inventory-stock-warnings', 'InventoryItemController@stockWarnings');
    Route::get('inventory-expiry-warnings', 'InventoryItemController@expiryWarnings');

    // Stock In Management
    Route::resource('stock-ins', 'StockInController');
    Route::post('stock-ins/{id}/confirm', 'StockInController@confirm');
    Route::post('stock-ins/{id}/cancel', 'StockInController@cancel');
    Route::resource('stock-in-items', 'StockInItemController');

    // Stock Out Management
    Route::resource('stock-outs', 'StockOutController');
    Route::post('stock-outs/{id}/confirm', 'StockOutController@confirm');
    Route::post('stock-outs/{id}/cancel', 'StockOutController@cancel');
    Route::resource('stock-out-items', 'StockOutItemController');

    // Service Consumables Configuration
    Route::resource('service-consumables', 'ServiceConsumableController');

    // ============================================================
    // Waiting Queue Management System (候诊与叫号)
    // ============================================================

    // Main queue management (receptionist)
    Route::get('waiting-queue', 'WaitingQueueController@index');
    Route::get('waiting-queue/data', 'WaitingQueueController@getData');
    Route::get('waiting-queue/today-appointments', 'WaitingQueueController@getTodayAppointments');
    Route::post('waiting-queue/check-in', 'WaitingQueueController@checkIn');
    Route::post('waiting-queue/{id}/call', 'WaitingQueueController@callPatient');
    Route::post('waiting-queue/{id}/start', 'WaitingQueueController@startTreatment');
    Route::post('waiting-queue/{id}/complete', 'WaitingQueueController@completeTreatment');
    Route::post('waiting-queue/{id}/cancel', 'WaitingQueueController@cancel');

    // Display screen (public display for waiting room)
    Route::get('waiting-queue/display', 'WaitingQueueController@displayScreen');
    Route::get('waiting-queue/display-data', 'WaitingQueueController@getDisplayData');

    // Doctor's queue management
    Route::get('doctor-queue', 'WaitingQueueController@doctorQueue');
    Route::post('doctor-queue/call-next', 'WaitingQueueController@callNext');

    // ============================================================
    // Reports - Patient Analytics
    // ============================================================

    // Patient Source Analysis Report
    Route::get('patient-source-report', 'PatientSourceReportController@index');

    // Revisit Rate Statistics Report
    Route::get('revisit-rate-report', 'RevisitRateReportController@index');

    // ============================================================
    // Patient Satisfaction Survey System
    // ============================================================

    Route::get('satisfaction-surveys', 'SatisfactionSurveyController@index');
    Route::get('satisfaction-surveys/data', 'SatisfactionSurveyController@getData');
    Route::get('satisfaction-surveys/create', 'SatisfactionSurveyController@create');
    Route::post('satisfaction-surveys', 'SatisfactionSurveyController@store');
    Route::get('satisfaction-surveys/{id}', 'SatisfactionSurveyController@show');
    Route::post('satisfaction-surveys/{id}/submit', 'SatisfactionSurveyController@submit');
    Route::post('satisfaction-surveys/send-batch', 'SatisfactionSurveyController@sendBatch');

    // ============================================================
    // Doctor Schedule Management
    // ============================================================

    Route::resource('doctor-schedules', 'DoctorScheduleController');
    Route::get('doctor-schedules/calendar', 'DoctorScheduleController@calendar');

    // ============================================================
    // Commission Rules Management
    // ============================================================

    Route::resource('commission-rules', 'CommissionRuleController');
    Route::post('commission-rules/calculate', 'CommissionRuleController@calculate');
});
