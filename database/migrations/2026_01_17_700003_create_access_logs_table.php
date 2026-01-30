<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('accessed_resource', 200);
            $table->string('resource_type', 100);
            $table->string('resource_id', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('access_time');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['user_id', 'access_time']);
            $table->index('access_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_logs');
    }
}
