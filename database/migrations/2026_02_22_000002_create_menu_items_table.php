<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title_key', 100);
            $table->string('url', 255)->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedBigInteger('permission_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order'], 'idx_parent_sort');
            $table->index('is_active', 'idx_active');
        });

        Schema::create('role_menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_item_id');

            $table->unique(['role_id', 'menu_item_id'], 'uk_role_menu');
            $table->index('role_id', 'idx_role');
            $table->index('menu_item_id', 'idx_menu_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_items');
        Schema::dropIfExists('menu_items');
    }
};
