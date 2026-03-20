<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(100)->after('price');
            $table->decimal('discounted_price', 14, 2)->nullable()->after('discount_rate');
            $table->decimal('actual_paid', 14, 2)->nullable()->after('discounted_price');
            $table->decimal('arrears', 14, 2)->default(0)->after('actual_paid');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_mode', 20)->default('direct')->after('status');
        });

        Schema::table('medical_services', function (Blueprint $table) {
            $table->string('unit', 20)->nullable()->default('次')->after('name');
        });

        // Add transaction_ref to invoice_payments if missing
        if (!Schema::hasColumn('invoice_payments', 'transaction_ref')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->string('transaction_ref', 100)->nullable()->after('bank_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount_rate', 'discounted_price', 'actual_paid', 'arrears']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('billing_mode');
        });

        Schema::table('medical_services', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        if (Schema::hasColumn('invoice_payments', 'transaction_ref')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->dropColumn('transaction_ref');
            });
        }
    }
};
