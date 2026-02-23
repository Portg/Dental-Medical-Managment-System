<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug', 50)->after('name')->default('');
        });

        // 回填 slug + 将 name 改为中文显示名
        $mapping = [
            'Super Administrator' => ['slug' => 'super-admin',  'name' => '超级管理员'],
            'Administrator'       => ['slug' => 'admin',        'name' => '管理员'],
            'Doctor'              => ['slug' => 'doctor',       'name' => '医生'],
            'Nurse'               => ['slug' => 'nurse',        'name' => '护士'],
            'Receptionist'        => ['slug' => 'receptionist', 'name' => '前台'],
        ];

        foreach ($mapping as $oldName => $data) {
            DB::table('roles')->where('name', $oldName)->update([
                'slug' => $data['slug'],
                'name' => $data['name'],
            ]);
        }

        // 为可能存在的其他自定义角色生成 slug
        DB::table('roles')->where('slug', '')->get()->each(function ($role) {
            DB::table('roles')->where('id', $role->id)->update([
                'slug' => \Illuminate\Support\Str::slug($role->name),
            ]);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        // 恢复英文名
        $mapping = [
            'super-admin'  => 'Super Administrator',
            'admin'        => 'Administrator',
            'doctor'       => 'Doctor',
            'nurse'        => 'Nurse',
            'receptionist' => 'Receptionist',
        ];

        foreach ($mapping as $slug => $name) {
            DB::table('roles')->where('slug', $slug)->update(['name' => $name]);
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
