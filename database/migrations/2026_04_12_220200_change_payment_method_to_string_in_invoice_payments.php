<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangePaymentMethodToStringInInvoicePayments extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Convert enum to varchar to support all payment methods (WeChat, Alipay, BankCard, etc.)
        DB::statement("ALTER TABLE invoice_payments MODIFY payment_method VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("ALTER TABLE invoice_payments MODIFY payment_method ENUM('Cash','Online Wallet','Insurance','Mobile Money','Cheque') NULL");
    }
}
