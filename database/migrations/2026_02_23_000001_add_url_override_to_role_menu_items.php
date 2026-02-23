<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_menu_items', function (Blueprint $table) {
            $table->string('url_override', 255)->nullable()->after('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('role_menu_items', function (Blueprint $table) {
            $table->dropColumn('url_override');
        });
    }
};
