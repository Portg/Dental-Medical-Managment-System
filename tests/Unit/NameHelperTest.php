<?php

namespace Tests\Unit;

use App\Http\Helper\NameHelper;
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
}
