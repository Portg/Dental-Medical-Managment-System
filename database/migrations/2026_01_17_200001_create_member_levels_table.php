<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_levels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('color')->default('#999999');
            $table->decimal('discount_rate', 5, 2)->default(100.00);
            $table->decimal('min_consumption', 10, 2)->default(0);
            $table->decimal('points_rate', 5, 2)->default(1.00);
            $table->text('benefits')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('member_levels');
    }
}
