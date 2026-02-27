<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_shared_holders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('primary_patient_id');
            $table->unsignedBigInteger('shared_patient_id');
            $table->string('relationship', 50);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['primary_patient_id', 'shared_patient_id'], 'shared_holder_unique');
            $table->foreign('primary_patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('shared_patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('_who_added')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_shared_holders');
    }
};
