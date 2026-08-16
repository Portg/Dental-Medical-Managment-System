<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 清掉「上一次回滚没清干净」留下的孤立表，让升级能继续。
 *
 * 怎么会有孤立表：升级跑完迁移之后回滚，而旧版本的备份是 mysqldump 不带
 * --databases 做的 —— 转储里只有各表的 DROP TABLE IF EXISTS + CREATE，没有
 * DROP DATABASE。导入它只覆盖「备份时就存在的表」，迁移新建的表原样留下，
 * 而 migrations 表被恢复成没跑过。于是库卡在：
 *
 *     表在   +   migrations 表里没有这条记录
 *
 * 此后每次升级都死在同一条 CREATE TABLE 上（1050 Table already exists），
 * 而 SQL 报错完全看不出跟上次回滚有关。备份侧的根因已经修掉（--databases +
 * 先 DROP DATABASE 再整库导入），但已经卡住的库修不了自己，需要这条命令。
 *
 * 安全边界 —— 两条都满足才动手，缺一条就停下来交给人：
 *   1. 只处理**待执行迁移**里 Schema::create() 声明的表。迁移还没跑过，
 *      说明这版应用根本不该在用这张表。
 *   2. 只删**空表**。有数据就绝不删，报出来让人判断 —— 宁可升级失败，
 *      也不能替人决定一份数据能不能扔。
 */
class RepairOrphanMigrationTables extends Command
{
    protected $signature = 'upgrade:repair-orphan-tables {--dry-run : 只报告，不实际删除}';

    protected $description = 'Drop empty tables left behind by a rolled-back upgrade so migrations can run again';

    public function handle(): int
    {
        if (!Schema::hasTable('migrations')) {
            $this->info('migrations 表不存在，应当是全新安装，无需修复。');

            return self::SUCCESS;
        }

        $orphans = $this->findOrphanTables();

        if ($orphans === []) {
            $this->info('没有发现孤立表。');

            return self::SUCCESS;
        }

        $empty = [];
        $nonEmpty = [];

        foreach ($orphans as $table => $migration) {
            $rows = (int) DB::table($table)->count();

            if ($rows === 0) {
                $empty[$table] = $migration;
            } else {
                $nonEmpty[$table] = ['migration' => $migration, 'rows' => $rows];
            }
        }

        foreach ($nonEmpty as $table => $info) {
            $this->error("表 {$table} 里有 {$info['rows']} 行数据，不删。");
            $this->line("  它由待执行的迁移 {$info['migration']} 创建，正常情况下不该有数据。");
        }

        if ($nonEmpty !== []) {
            $this->newLine();
            $this->error('有孤立表带着数据，已停止 —— 这份数据能不能扔只有你知道。');
            $this->line('请人工确认后自行处理，再重新运行升级。');

            return self::FAILURE;
        }

        foreach ($empty as $table => $migration) {
            if ($this->option('dry-run')) {
                $this->line("将删除空的孤立表: {$table}  (来自 {$migration})");
                continue;
            }

            // 这些表是孤立的空表，不会有别的表合法地引用它们；
            // 但它们自己带着指向 users / branches 的外键，关掉检查更稳妥。
            Schema::disableForeignKeyConstraints();
            Schema::drop($table);
            Schema::enableForeignKeyConstraints();

            $this->warn("已删除空的孤立表: {$table}  (来自 {$migration}，将由该迁移重新创建)");
        }

        return self::SUCCESS;
    }

    /**
     * 待执行迁移声明要创建、但库里已经存在的表。
     *
     * @return array<string, string> 表名 => 迁移名
     */
    private function findOrphanTables(): array
    {
        $ran = DB::table('migrations')->pluck('migration')->flip();
        $orphans = [];

        foreach (glob(database_path('migrations/*.php')) as $file) {
            $name = basename($file, '.php');

            if ($ran->has($name)) {
                continue;
            }

            $source = (string) file_get_contents($file);

            if (!preg_match_all('/Schema::create\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $source, $matches)) {
                continue;
            }

            foreach ($matches[1] as $table) {
                if (Schema::hasTable($table)) {
                    $orphans[$table] = $name;
                }
            }
        }

        return $orphans;
    }
}
