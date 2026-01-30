<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceAndCategoryToMedicalServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medical_services', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('name');
            $table->string('category', 50)->nullable()->after('price');
            $table->text('description')->nullable()->after('category');
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medical_services', function (Blueprint $table) {
            $table->dropColumn(['price', 'category', 'description', 'is_active']);
        });
    }
}
