<?php

namespace Tests\Unit;

use App\Http\Helper\NameHelper;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NameHelperTest extends TestCase
{
    // ─── split() ─────────────────────────────────────────────────

    public function test_split_single_char_surname(): void
    {
        $result = NameHelper::split('张三丰');

        $this->assertEquals('张', $result['surname']);
        $this->assertEquals('三丰', $result['othername']);
    }

    public function test_split_compound_surname(): void
    {
        $result = NameHelper::split('欧阳修');

        $this->assertEquals('欧阳', $result['surname']);
        $this->assertEquals('修', $result['othername']);
    }

    public function test_split_all_compound_surnames(): void
    {
        $compoundSurnames = [
            '欧阳', '太史', '端木', '上官', '司马', '东方', '独孤', '南宫',
            '万俟', '闻人', '夏侯', '诸葛', '尉迟', '公羊', '赫连', '澹台',
            '皇甫', '宗政', '濮阳', '公冶', '太叔', '申屠', '公孙', '慕容',
            '仲孙', '钟离', '长孙', '宇文', '司徒', '鲜于', '司空', '令狐',
        ];

        foreach ($compoundSurnames as $cs) {
            $result = NameHelper::split($cs . '测试');
            $this->assertEquals($cs, $result['surname'], "Failed for compound surname: {$cs}");
            $this->assertEquals('测试', $result['othername'], "Failed othername for: {$cs}");
        }
    }

    public function test_split_empty_string(): void
    {
        $result = NameHelper::split('');

        $this->assertEquals('', $result['surname']);
        $this->assertEquals('', $result['othername']);
    }

    public function test_split_single_char(): void
    {
        $result = NameHelper::split('李');

        $this->assertEquals('李', $result['surname']);
        $this->assertEquals('', $result['othername']);
    }

    public function test_split_non_chinese_name(): void
    {
        $result = NameHelper::split('John');

        $this->assertEquals('J', $result['surname']);
        $this->assertEquals('ohn', $result['othername']);
    }

    // ─── join() ──────────────────────────────────────────────────

    public function test_join_zh_cn_no_space(): void
    {
        app()->setLocale('zh-CN');

        $result = NameHelper::join('张', '三丰');

        $this->assertEquals('张三丰', $result);
    }

    public function test_join_en_with_space(): void
    {
        app()->setLocale('en');

        $result = NameHelper::join('Zhang', 'Sanfeng');

        $this->assertEquals('Zhang Sanfeng', $result);
    }

    // ─── addNameSearch() 的 OR 分组 ──────────────────────────────
    //
    // 这两条断言的是编译出来的 SQL 结构，不连库。判断标准就在 SQL 里，
    // 用数据行去测反而看不出 OR 优先级是怎么错的。

    /**
     * 调用点把姓名条件挂在闭包里已有条件后面时（InvoiceService::searchInvoices
     * 就是这么用的），整组姓名条件必须是一个 OR 分组。
     *
     * 不分组的话，第一句 where 会和前面那个条件 AND 掉：
     *     invoice_no like ? AND surname like ? OR othername like ? OR ...
     * 发票号是数字串、姓氏是中文，两者不可能同时命中 —— 按发票号搜发票
     * 一条都搜不出来，搜到的全是按姓名命中的。
     */
    public function test_姓名条件挂在已有条件后面时整组走OR(): void
    {
        app()->setLocale('zh-CN');

        $sql = DB::table('invoices')
            ->where(function ($q) {
                $q->where('invoices.invoice_no', 'like', '%INV001%');
                NameHelper::addNameSearch($q, 'INV001', 'patients');
            })
            ->toSql();

        $this->assertStringNotContainsString(
            'like ? and `patients`.`surname`',
            $sql,
            '姓名条件被 AND 到了前面的发票号上'
        );
        $this->assertStringContainsString(
            'or (`patients`.`surname`',
            $sql,
            '整组姓名条件应当作为一个 OR 分组挂上去'
        );
    }

    /**
     * 另外 17 个调用点是在空闭包里调的。分组之后这里必须还是一条纯 OR 链 ——
     * 查询编译时会丢掉首个条件的 boolean，所以多包一层不会凭空多出个 AND。
     */
    public function test_空闭包里调用时不会多出AND(): void
    {
        app()->setLocale('zh-CN');

        $sql = DB::table('patients')
            ->where(function ($q) {
                NameHelper::addNameSearch($q, 'lwy', 'patients');
            })
            ->toSql();

        $this->assertStringContainsString('`patients`.`name_py` like ?', $sql, '首拼条件应当在分组里');
        $this->assertStringNotContainsString('and `patients`', $sql, '空闭包里不该出现 AND');
    }
}
