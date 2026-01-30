<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('rule_name', 100);
            $table->enum('commission_mode', ['fixed_percentage', 'tiered', 'fixed_amount', 'mixed'])->default('fixed_percentage');
            $table->string('target_service_type', 100)->nullable();
            $table->unsignedBigInteger('medical_service_id')->nullable();
            $table->decimal('base_commission_rate', 5, 2)->default(0);
            $table->decimal('tier1_threshold', 14, 2)->nullable();
            $table->decimal('tier1_rate', 5, 2)->nullable();
            $table->decimal('tier2_threshold', 14, 2)->nullable();
            $table->decimal('tier2_rate', 5, 2)->nullable();
            $table->decimal('tier3_threshold', 14, 2)->nullable();
            $table->decimal('tier3_rate', 5, 2)->nullable();
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('medical_service_id')->references('id')->on('medical_services');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('_who_added')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commission_rules');
    }
}
