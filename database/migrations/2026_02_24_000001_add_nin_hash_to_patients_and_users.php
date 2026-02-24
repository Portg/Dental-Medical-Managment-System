<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('nin')->nullable()->change();
            $table->string('nin_hash', 64)->nullable()->index()->after('nin');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('nin')->nullable()->change();
            $table->string('nin_hash', 64)->nullable()->index()->after('nin');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['nin_hash']);
            $table->dropColumn('nin_hash');
            $table->string('nin')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nin_hash']);
            $table->dropColumn('nin_hash');
            $table->string('nin')->nullable()->change();
        });
    }
};
