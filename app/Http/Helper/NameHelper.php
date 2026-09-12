<?php

namespace App\Http\Helper;

class NameHelper
{
    private static $compoundSurnames = [
        '欧阳', '太史', '端木', '上官', '司马', '东方', '独孤', '南宫',
        '万俟', '闻人', '夏侯', '诸葛', '尉迟', '公羊', '赫连', '澹台',
        '皇甫', '宗政', '濮阳', '公冶', '太叔', '申屠', '公孙', '慕容',
        '仲孙', '钟离', '长孙', '宇文', '司徒', '鲜于', '司空', '令狐',
    ];

    /**
     * Split a Chinese full name into surname and given name.
     */
    public static function split(string $fullName): array
    {
        $fullName = trim($fullName);

        if ($fullName === '') {
            return ['surname' => '', 'othername' => ''];
        }

        foreach (self::$compoundSurnames as $cs) {
            if (mb_strpos($fullName, $cs) === 0 && mb_strlen($fullName) > mb_strlen($cs)) {
                return [
                    'surname'   => $cs,
                    'othername' => mb_substr($fullName, mb_strlen($cs)),
                ];
            }
        }

        return [
            'surname'   => mb_substr($fullName, 0, 1),
            'othername' => mb_substr($fullName, 1),
        ];
    }

    /**
     * Join surname and othername with locale-aware separator.
     * Works with both Eloquent models and raw DB objects.
     */
    public static function join($surname, $othername): string
    {
        if (app()->getLocale() === 'zh-CN') {
            return $surname . $othername;
        }
        return $surname . ' ' . $othername;
    }

    /**
     * 姓名的首拼缩写：刘万友 → lwy，Admin User → adminuser。
     *
     * 前台接电话时打 lwy 比打中文快得多，这是中文诊所软件的标配检索方式。
     * 落库存下来（patients.name_py）而不是查询时算：查询时算就没法走索引，
     * 也没法用 LIKE 前缀匹配。
     */
    public static function abbr(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        // 纯英文名不必惊动拼音库
        if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $name)) {
            return strtolower(preg_replace('/[^a-zA-Z]/', '', $name));
        }

        // 进程内记忆化：每次调用拼音库会有约 8MB 的瞬时峰值（词典用生成器读，
        // 读完就释放，驻留只有 0.1MB）。生产上无所谓，但测试进程本来就贴着
        // 128M 跑，一个套件里成百次建患者，反复顶这个峰值会把别的测试撞 OOM。
        static $memo = [];

        if (!isset($memo[$name])) {
            // nameAbbr 而不是 abbr：前者按「姓名」处理，复姓走 surnames.php
            // （单雄信 → xxs 而不是 dxx，欧阳娜娜 → oynn），后者按普通词组切。
            $memo[$name] = strtolower(\Overtrue\Pinyin\Pinyin::nameAbbr($name)->join(''));
        }

        return $memo[$name];
    }

    /**
     * Add name search conditions to a query builder.
     * In zh-CN, also matches CONCAT(surname, othername) for full-name search.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @param string $table  Table name prefix (e.g. 'patients', 'users'), empty for no prefix
     * @param string|null $pinyinColumn 首拼列名，null 表示该表没有首拼列
     *
     * 默认值是 'name_py'（患者表的首拼列），而不是 null。理由是调用点 18 个里
     * 15 个是患者表：默认关掉的话，将来新加的患者侧搜索会**静默地**没有首拼，
     * 谁也不会发现；默认打开的话，忘了给非患者表传 null 会立刻报「未知列」，
     * 测试当场就红。少一个功能不会有人报错，SQL 报错会。
     */
    public static function addNameSearch($query, string $search, string $table = '', ?string $pinyinColumn = 'name_py')
    {
        $surnameCol = $table ? "{$table}.surname" : 'surname';
        $othernameCol = $table ? "{$table}.othername" : 'othername';

        // 整组条件作为一个 OR 分组挂上去，而不是直接往 $query 上铺 where + orWhere。
        //
        // 调用点如果在闭包里已经有自己的条件（InvoiceService::searchInvoices 就是
        // 先放了 invoice_no），裸铺的第一句 where 会被 AND 到那个条件上：
        //     invoice_no like ? AND surname like ? OR othername like ? OR ...
        // 发票号是数字串、姓氏是中文，两者不可能同时命中 —— 按发票号搜发票
        // 一条都搜不出来。分组之后 OR 的语义才是完整的。
        //
        // 另外 17 个调用点在空闭包里调，不受影响：查询编译时会丢掉首个条件的
        // boolean，所以多包一层不会凭空多出一个 AND。
        $query->orWhere(function ($q) use ($search, $table, $surnameCol, $othernameCol, $pinyinColumn) {
            $q->where($surnameCol, 'like', '%' . $search . '%')
              ->orWhere($othernameCol, 'like', '%' . $search . '%');

            if (app()->getLocale() === 'zh-CN') {
                $q->orWhereRaw("CONCAT({$surnameCol}, {$othernameCol}) like ?", ['%' . $search . '%']);
            }

            // 首拼：前缀匹配而不是两头模糊。lwy 应当命中「刘万友」，
            // 但 wy 不该命中 —— 两头模糊会让任意两三个字母扫出一大片无关患者。
            if ($pinyinColumn !== null && $search !== '' && preg_match('/^[a-zA-Z]+$/', $search)) {
                $pyCol = $table ? "{$table}.{$pinyinColumn}" : $pinyinColumn;
                $q->orWhere($pyCol, 'like', strtolower($search) . '%');
            }
        });
    }
}
