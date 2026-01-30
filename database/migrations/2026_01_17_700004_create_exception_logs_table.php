<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExceptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exception_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exception_type', 200);
            $table->text('message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('request_url', 500)->nullable();
            $table->string('request_method', 10)->nullable();
            $table->json('request_data')->nullable();
            $table->integer('response_status')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index('occurred_at');
            $table->index(['exception_type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exception_logs');
    }
}
