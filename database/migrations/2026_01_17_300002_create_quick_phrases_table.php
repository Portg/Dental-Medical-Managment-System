<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickPhrasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quick_phrases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shortcut', 20);
            $table->string('phrase');
            $table->string('category')->nullable();
            $table->enum('scope', ['system', 'personal'])->default('system');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('_who_added');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quick_phrases');
    }
}
