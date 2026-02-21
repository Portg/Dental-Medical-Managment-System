<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DentalChartController;
use App\Http\Controllers\Api\V1\DoctorClaimController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LabCaseController;
use App\Http\Controllers\Api\V1\LabController;
use App\Http\Controllers\Api\V1\InvoicePaymentController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\MedicalCaseController;
use App\Http\Controllers\Api\V1\MedicalServiceController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MemberLevelController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Controllers\Api\V1\QuotationController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TreatmentController;
use App\Http\Controllers\Api\V1\TreatmentPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api/v1 and use the 'api' middleware
| group. Routes below (except auth/login) require auth:sanctum.
|
*/

// Auth — login does NOT require auth:sanctum, uses stricter rate limit
Route::withoutMiddleware('auth:sanctum')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
});

Route::post('auth/logout', [AuthController::class, 'logout']);
Route::get('auth/me', [AuthController::class, 'me']);

// Patients
Route::get('patients/search', [PatientController::class, 'search']);
Route::get('patients/{id}/medical-history', [PatientController::class, 'medicalHistory']);
Route::apiResource('patients', PatientController::class);

// Appointments
Route::get('appointments/calendar-events', [AppointmentController::class, 'calendarEvents']);
Route::get('appointments/chairs', [AppointmentController::class, 'chairs']);
Route::get('appointments/doctor-time-slots', [AppointmentController::class, 'doctorTimeSlots']);
Route::post('appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
Route::apiResource('appointments', AppointmentController::class);

// Invoices
Route::get('invoices/search', [InvoiceController::class, 'search']);
Route::get('invoices/{id}/amount', [InvoiceController::class, 'amount']);
Route::get('invoices/{id}/procedures', [InvoiceController::class, 'procedures']);
Route::post('invoices/{id}/approve-discount', [InvoiceController::class, 'approveDiscount']);
Route::post('invoices/{id}/reject-discount', [InvoiceController::class, 'rejectDiscount']);
Route::post('invoices/{id}/set-credit', [InvoiceController::class, 'setCredit']);
Route::apiResource('invoices', InvoiceController::class)->except(['update']);

// Medical Cases
Route::get('medical-cases/icd10-search', [MedicalCaseController::class, 'icd10Search']);
Route::get('medical-cases/patient/{patientId}', [MedicalCaseController::class, 'patientCases']);
Route::apiResource('medical-cases', MedicalCaseController::class);

// Medical Cases — Compliance (amendments, version history, PDF)
Route::get('medical-cases/{id}/amendments', [MedicalCaseController::class, 'amendments']);
Route::get('medical-cases/{id}/version-history', [MedicalCaseController::class, 'versionHistory']);
Route::get('medical-cases/{id}/export-pdf', [MedicalCaseController::class, 'exportPdf']);
Route::post('medical-cases/{id}/archive-pdf', [MedicalCaseController::class, 'archivePdf']);
Route::post('medical-case-amendments/{id}/approve', [MedicalCaseController::class, 'approveAmendment']);
Route::post('medical-case-amendments/{id}/reject', [MedicalCaseController::class, 'rejectAmendment']);

// ─── Group A: Inventory / Members / Doctor Claims ─────────────────────

// Inventory Items
Route::get('inventory-items/search', [InventoryItemController::class, 'search']);
Route::get('inventory-items/low-stock', [InventoryItemController::class, 'lowStock']);
Route::get('inventory-items/expiring', [InventoryItemController::class, 'expiring']);
Route::apiResource('inventory-items', InventoryItemController::class);

// Members
Route::post('members/register', [MemberController::class, 'register']);
Route::post('members/{id}/deposit', [MemberController::class, 'deposit']);
Route::get('members/{id}/transactions', [MemberController::class, 'transactions']);
Route::apiResource('members', MemberController::class)->except(['store', 'destroy']);

// Member Levels
Route::apiResource('member-levels', MemberLevelController::class);

// Doctor Claims
Route::post('doctor-claims/{id}/approve', [DoctorClaimController::class, 'approve']);
Route::apiResource('doctor-claims', DoctorClaimController::class);

// ─── Group B: Clinical Data ──────────────────────────────────────────

// Treatments
Route::apiResource('treatments', TreatmentController::class);

// Treatment Plans
Route::get('treatment-plans/patient/{patientId}', [TreatmentPlanController::class, 'patientPlans']);
Route::apiResource('treatment-plans', TreatmentPlanController::class);

// Prescriptions
Route::get('prescriptions/appointment/{appointmentId}', [PrescriptionController::class, 'byAppointment']);
Route::get('prescriptions/drug-names', [PrescriptionController::class, 'drugNames']);
Route::apiResource('prescriptions', PrescriptionController::class);

// Dental Charts
Route::get('dental-charts/patient/{patientId}', [DentalChartController::class, 'patientChart']);
Route::get('dental-charts/appointment/{appointmentId}', [DentalChartController::class, 'appointmentChart']);
Route::get('dental-charts', [DentalChartController::class, 'index']);
Route::post('dental-charts', [DentalChartController::class, 'store']);

// ─── Group C: Billing Extensions ─────────────────────────────────────

// Invoice Payments
Route::post('invoice-payments/{invoiceId}/process-mixed', [InvoicePaymentController::class, 'processMixed']);
Route::get('invoice-payments/payment-methods', [InvoicePaymentController::class, 'paymentMethods']);
Route::apiResource('invoice-payments', InvoicePaymentController::class);

// Refunds
Route::get('refunds/pending', [RefundController::class, 'pendingApprovals']);
Route::post('refunds/{id}/approve', [RefundController::class, 'approve']);
Route::post('refunds/{id}/reject', [RefundController::class, 'reject']);
Route::apiResource('refunds', RefundController::class)->except(['update']);

// Quotations
Route::apiResource('quotations', QuotationController::class)->except(['update']);

// ─── Group D: Master Data ────────────────────────────────────────────

// Medical Services
Route::apiResource('medical-services', MedicalServiceController::class);

// Suppliers
Route::apiResource('suppliers', SupplierController::class);

// ─── Group E: Lab Case Management ───────────────────────────────────

// Labs (技工厂)
Route::apiResource('labs', LabController::class);

// Lab Cases (技工单)
Route::get('lab-cases/overdue', [LabCaseController::class, 'overdue']);
Route::get('lab-cases/statistics', [LabCaseController::class, 'statistics']);
Route::get('lab-cases/options', [LabCaseController::class, 'options']);
Route::get('lab-cases/patient/{patientId}', [LabCaseController::class, 'patientCases']);
Route::post('lab-cases/{id}/status', [LabCaseController::class, 'updateStatus']);
Route::apiResource('lab-cases', LabCaseController::class);
