<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('transaction_no')->unique();
            $table->enum('transaction_type', ['Deposit', 'Consumption', 'Refund', 'Adjustment', 'Points'])->default('Deposit');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2)->default(0);
            $table->decimal('balance_after', 10, 2)->default(0);
            $table->decimal('points_change', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('_who_added')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_transactions');
    }
}
