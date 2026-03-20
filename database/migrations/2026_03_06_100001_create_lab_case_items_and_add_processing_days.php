<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create lab_case_items table
        Schema::create('lab_case_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_case_id');
            $table->string('prosthesis_type');
            $table->string('material')->nullable();
            $table->string('color_shade', 50)->nullable();
            $table->json('teeth_positions')->nullable();
            $table->unsignedSmallInteger('qty')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('lab_case_id')
                ->references('id')->on('lab_cases')
                ->onDelete('cascade');
        });

        // 2. Migrate existing data: each lab_case with prosthesis_type → one lab_case_items row
        $cases = DB::table('lab_cases')
            ->whereNotNull('prosthesis_type')
            ->where('prosthesis_type', '!=', '')
            ->whereNull('deleted_at')
            ->get(['id', 'prosthesis_type', 'material', 'color_shade', 'teeth_positions']);

        foreach ($cases as $case) {
            DB::table('lab_case_items')->insert([
                'lab_case_id'      => $case->id,
                'prosthesis_type'  => $case->prosthesis_type,
                'material'         => $case->material,
                'color_shade'      => $case->color_shade,
                'teeth_positions'  => $case->teeth_positions,
                'qty'              => 1,
                'sort_order'       => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // Also migrate soft-deleted cases for data completeness
        $deletedCases = DB::table('lab_cases')
            ->whereNotNull('prosthesis_type')
            ->where('prosthesis_type', '!=', '')
            ->whereNotNull('deleted_at')
            ->get(['id', 'prosthesis_type', 'material', 'color_shade', 'teeth_positions']);

        foreach ($deletedCases as $case) {
            DB::table('lab_case_items')->insert([
                'lab_case_id'      => $case->id,
                'prosthesis_type'  => $case->prosthesis_type,
                'material'         => $case->material,
                'color_shade'      => $case->color_shade,
                'teeth_positions'  => $case->teeth_positions,
                'qty'              => 1,
                'sort_order'       => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // 3. Add processing_days to lab_cases
        Schema::table('lab_cases', function (Blueprint $table) {
            $table->unsignedSmallInteger('processing_days')->default(7)->after('lab_id');
        });

        // 4. Drop old columns from lab_cases
        Schema::table('lab_cases', function (Blueprint $table) {
            $table->dropColumn(['prosthesis_type', 'material', 'color_shade', 'teeth_positions']);
        });
    }

    public function down(): void
    {
        // Re-add columns to lab_cases
        Schema::table('lab_cases', function (Blueprint $table) {
            $table->string('prosthesis_type')->nullable()->after('lab_id');
            $table->string('material')->nullable()->after('prosthesis_type');
            $table->string('color_shade', 50)->nullable()->after('material');
            $table->json('teeth_positions')->nullable()->after('color_shade');
        });

        // Migrate data back from lab_case_items to lab_cases (first item only)
        $items = DB::table('lab_case_items')
            ->where('sort_order', 0)
            ->get(['lab_case_id', 'prosthesis_type', 'material', 'color_shade', 'teeth_positions']);

        foreach ($items as $item) {
            DB::table('lab_cases')->where('id', $item->lab_case_id)->update([
                'prosthesis_type' => $item->prosthesis_type,
                'material'        => $item->material,
                'color_shade'     => $item->color_shade,
                'teeth_positions' => $item->teeth_positions,
            ]);
        }

        // Drop processing_days
        Schema::table('lab_cases', function (Blueprint $table) {
            $table->dropColumn('processing_days');
        });

        // Drop lab_case_items table
        Schema::dropIfExists('lab_case_items');
    }
};
