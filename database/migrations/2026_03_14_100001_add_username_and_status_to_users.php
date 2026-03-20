<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Overtrue\Pinyin\Pinyin;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('id');
            $table->enum('status', ['active', 'resigned'])->default('active')->after('is_doctor');
        });

        // 为现有用户生成拼音用户名（AG-028: 重复追加数字后缀）
        $this->generateUsernamesForExistingUsers();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'status']);
        });
    }

    private function generateUsernamesForExistingUsers(): void
    {
        $users = DB::table('users')->whereNull('deleted_at')->get(['id', 'surname', 'othername', 'email']);
        $usedUsernames = [];

        foreach ($users as $user) {
            // admin 账号直接指定 username，不走自动生成
            if ($user->email === 'admin@example.com') {
                DB::table('users')->where('id', $user->id)->update(['username' => 'admin']);
                $usedUsernames[] = 'admin';
                continue;
            }

            $fullName = $user->surname . $user->othername;
            $base = $this->toPinyinAbbr($fullName);

            if (empty($base)) {
                $base = 'user' . $user->id;
            }

            $username = $base;
            $counter = 1;
            while (in_array($username, $usedUsernames)) {
                $username = $base . $counter;
                $counter++;
            }

            $usedUsernames[] = $username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    private function toPinyinAbbr(string $name): string
    {
        if (empty(trim($name))) {
            return '';
        }

        // 中文：用拼音首字母缩写，去掉 v6 库默认的空格分隔
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $name)) {
            $pinyin = new Pinyin();
            $abbr = $pinyin->abbr($name, '');
            return strtolower(str_replace(' ', '', $abbr));
        }

        // 英文：直接用小写字母（去除非字母字符）
        return strtolower(preg_replace('/[^a-zA-Z]/', '', $name));
    }
};
