<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing indexes for query performance.
 *
 * - deleted_at indexes on all soft-delete tables (every list query filters by this)
 * - Search columns: patient_no, phone_no
 * - Filter columns: is_doctor, status, expected_return_date
 * - Composite indexes for common query patterns
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== Phase 1: deleted_at indexes (CRITICAL - every query filters on this) =====
        $softDeleteTables = [
            'patients',
            'appointments',
            'invoices',
            'invoice_items',
            'invoice_payments',
            'medical_cases',
            'treatments',
            'doctor_claims',
            'doctor_claim_payments',
            'quotations',
            'quotation_items',
            'lab_cases',
            'labs',
            'expenses',
            'expense_items',
            'expense_payments',
            'expense_categories',
            'medical_services',
            'patient_sources',
            'patient_tags',
            'patient_images',
            'patient_followups',
            'inventory_items',
            'inventory_categories',
            'stock_ins',
            'stock_outs',
            'refunds',
            'treatment_plans',
            'medical_templates',
            'progress_notes',
            'prescriptions',
        ];

        foreach ($softDeleteTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->index('deleted_at', $table . '_deleted_at_index');
                });
            }
        }

        // ===== Phase 2: Search & filter columns =====

        // patients: search by patient_no, phone_no
        Schema::table('patients', function (Blueprint $table) {
            $table->index('patient_no');
            $table->index('phone_no');
        });

        // users: frequently filtered by is_doctor
        if (Schema::hasColumn('users', 'is_doctor')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('is_doctor');
            });
        }

        // lab_cases: status filtering + overdue queries
        if (Schema::hasTable('lab_cases')) {
            Schema::table('lab_cases', function (Blueprint $table) {
                $table->index('status');
                $table->index('expected_return_date');
            });
        }

        // invoices: status filtering
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('status', 'invoices_status_index');
        });

        // ===== Phase 3: Composite indexes for common query patterns =====

        // appointments: doctor schedule lookup (WHERE doctor_id = ? AND sort_by = ?)
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['doctor_id', 'sort_by'], 'appointments_doctor_schedule_index');
            $table->index(['patient_id', 'deleted_at'], 'appointments_patient_soft_index');
        });

        // invoices: appointment lookup with soft delete
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['appointment_id', 'deleted_at'], 'invoices_appointment_soft_index');
        });

        // invoice_items: grouped sums per invoice
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index(['invoice_id', 'deleted_at'], 'invoice_items_invoice_soft_index');
        });

        // invoice_payments: payment sums per invoice
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->index(['invoice_id', 'deleted_at'], 'invoice_payments_invoice_soft_index');
        });

        // lab_cases: overdue query pattern (status + expected_return_date + actual_return_date)
        if (Schema::hasTable('lab_cases')) {
            Schema::table('lab_cases', function (Blueprint $table) {
                $table->index(
                    ['status', 'expected_return_date', 'actual_return_date'],
                    'lab_cases_overdue_index'
                );
            });
        }
    }

    public function down(): void
    {
        $softDeleteTables = [
            'patients',
            'appointments',
            'invoices',
            'invoice_items',
            'invoice_payments',
            'medical_cases',
            'treatments',
            'doctor_claims',
            'doctor_claim_payments',
            'quotations',
            'quotation_items',
            'lab_cases',
            'labs',
            'expenses',
            'expense_items',
            'expense_payments',
            'expense_categories',
            'medical_services',
            'patient_sources',
            'patient_tags',
            'patient_images',
            'patient_followups',
            'inventory_items',
            'inventory_categories',
            'stock_ins',
            'stock_outs',
            'refunds',
            'treatment_plans',
            'medical_templates',
            'progress_notes',
            'prescriptions',
        ];

        foreach ($softDeleteTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropIndex($table . '_deleted_at_index');
                });
            }
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['patient_no']);
            $table->dropIndex(['phone_no']);
        });

        if (Schema::hasColumn('users', 'is_doctor')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['is_doctor']);
            });
        }

        if (Schema::hasTable('lab_cases')) {
            Schema::table('lab_cases', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['expected_return_date']);
                $table->dropIndex('lab_cases_overdue_index');
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_index');
            $table->dropIndex('invoices_appointment_soft_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_doctor_schedule_index');
            $table->dropIndex('appointments_patient_soft_index');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('invoice_items_invoice_soft_index');
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropIndex('invoice_payments_invoice_soft_index');
        });
    }
};
