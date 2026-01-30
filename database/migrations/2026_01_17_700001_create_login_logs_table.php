<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username', 100)->nullable();
            $table->dateTime('login_time');
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 500)->nullable();
            $table->enum('login_status', ['success', 'failed'])->default('success');
            $table->string('failure_reason', 200)->nullable();
            $table->string('session_id', 100)->nullable();
            $table->dateTime('logout_time')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'login_time']);
            $table->index('login_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
}
