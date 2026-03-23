<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_services', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('service_categories')->nullOnDelete();
            $table->boolean('is_discountable')->default(true)->after('is_active');
            $table->boolean('is_favorite')->default(false)->after('is_discountable');
            $table->integer('sort_order')->default(0)->after('is_favorite');
        });

        // 回填旧 category varchar → service_categories + category_id FK
        $categories = DB::table('medical_services')
            ->whereNull('deleted_at')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->pluck('category');

        foreach ($categories as $catName) {
            $catId = DB::table('service_categories')->insertGetId([
                'name'       => $catName,
                'sort_order' => 0,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('medical_services')
                ->where('category', $catName)
                ->whereNull('deleted_at')
                ->update(['category_id' => $catId]);
        }
    }

    public function down(): void
    {
        Schema::table('medical_services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'is_discountable', 'is_favorite', 'sort_order']);
        });
    }
};
