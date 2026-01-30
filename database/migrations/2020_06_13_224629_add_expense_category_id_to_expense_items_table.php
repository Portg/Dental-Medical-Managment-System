<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpenseCategoryIdToExpenseItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expense_items', function (Blueprint $table) {
            if (Schema::hasColumn('expense_items', 'name')) {
                $table->unsignedBigInteger('expense_category_id')->nullable()->after('name');
            } else {
                $table->unsignedBigInteger('expense_category_id')->nullable();
            }
            $table->foreign('expense_category_id')->references('id')->on('expense_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn('expense_category_id');
        });
    }
}
