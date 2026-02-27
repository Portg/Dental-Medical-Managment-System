<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id');
            $table->string('action', 50);
            $table->string('field_name', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('_who_added')->references('id')->on('users')->onDelete('set null');

            $table->index('patient_id');
            $table->index('action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_audit_logs');
    }
};
