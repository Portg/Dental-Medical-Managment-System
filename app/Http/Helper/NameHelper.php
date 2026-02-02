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
     * Add name search conditions to a query builder.
     * In zh-CN, also matches CONCAT(surname, othername) for full-name search.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @param string $table  Table name prefix (e.g. 'patients', 'users'), empty for no prefix
     */
    public static function addNameSearch($query, string $search, string $table = '')
    {
        $surnameCol = $table ? "{$table}.surname" : 'surname';
        $othernameCol = $table ? "{$table}.othername" : 'othername';

        $query->where($surnameCol, 'like', '%' . $search . '%')
              ->orWhere($othernameCol, 'like', '%' . $search . '%');

        if (app()->getLocale() === 'zh-CN') {
            $query->orWhereRaw("CONCAT({$surnameCol}, {$othernameCol}) like ?", ['%' . $search . '%']);
        }
    }
}
