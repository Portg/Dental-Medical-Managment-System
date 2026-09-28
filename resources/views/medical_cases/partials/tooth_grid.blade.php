{{-- 牙位网格：恒牙与乳牙同屏。

     取消了原来的「恒牙 / 乳牙」tab 切换。分 tab 不是慢一点的问题，是**写不出来**：
     6 到 12 岁是替牙期，一张嘴里两种牙并存。一个 8 岁患儿要写「55 乳牙滞留，
     15 阻生」——替牙期最常见的一条记录——分 tab 就必须中途切换；而原先那个
     「按年龄自动选 tab（≤12 岁默认乳牙）」的贴心设计，恰好在替牙期把人送到
     错误的那一面。

     排布与参考产品一致：每个象限里乳牙在外、恒牙在内，中间一条中线分左右。

         全 │ 55 54 53 52 51 │ 61 62 63 64 65 │ 全     ← 乳牙（外）
            │ 18 …        11 │ 21 …        28 │        ← 恒牙（内）
            ──────── 右 ── 左 ────────
            │ 48 …        41 │ 31 …        38 │
         全 │ 85 84 83 82 81 │ 71 72 73 74 75 │ 全

     乳牙用琥珀色、恒牙用墨色 —— 颜色区分代替 tab 切换，而且一眼看得出这次动的
     是哪一种牙。

     参数：
       $idPrefix  生成的元素 id 前缀（弹窗与侧栏各一份，避免 id 重复）
       $compact   侧栏用的紧凑尺寸
       $onclick   每个牙位格的 onclick（弹窗用 toggleTooth，侧栏走事件委托传 null）
--}}
@php
    $idPrefix = $idPrefix ?? 'tooth-grid';
    $compact  = $compact ?? false;
    $onclick  = $onclick ?? null;

    /**
     * 格子上显示什么 —— 部位记录法：恒牙 1-8、乳牙 Ⅰ-Ⅴ，象限靠格子在十字里的
     * 位置表示，不写在数字上。这是中文牙科的通行写法，也是参考产品的写法。
     *
     * data-tooth 仍然存 FDI 全码（16、55）：那是落库和查询用的。显示与存储分开，
     * 与行内十字图（toothSymbol）同一套规则 —— 原来网格显示全码，和它自己的行
     * 对不上，医生在网格上点的是「15」，行里却显示成「5」。
     */
    $symbol = function (int $t): string {
        $quad = intdiv($t, 10);
        $pos  = $t % 10;
        $roman = ['', 'Ⅰ', 'Ⅱ', 'Ⅲ', 'Ⅳ', 'Ⅴ'];
        return $quad >= 5 ? ($roman[$pos] ?? (string) $t) : (string) $pos;
    };

    // [象限 => [乳牙, 恒牙]]，外→内的顺序
    $quadrants = [
        'ur' => ['milk' => [55, 54, 53, 52, 51], 'perm' => [18, 17, 16, 15, 14, 13, 12, 11]],
        'ul' => ['milk' => [61, 62, 63, 64, 65], 'perm' => [21, 22, 23, 24, 25, 26, 27, 28]],
        'lr' => ['milk' => [85, 84, 83, 82, 81], 'perm' => [48, 47, 46, 45, 44, 43, 42, 41]],
        'll' => ['milk' => [71, 72, 73, 74, 75], 'perm' => [31, 32, 33, 34, 35, 36, 37, 38]],
    ];
@endphp

<div class="tg {{ $compact ? 'tg-compact' : '' }}" id="{{ $idPrefix }}">
    @foreach(['upper' => ['ur', 'ul'], 'lower' => ['lr', 'll']] as $half => $pair)
        @php($rows = $half === 'upper' ? ['milk', 'perm'] : ['perm', 'milk'])

        @foreach($rows as $dent)
            <div class="tg-row tg-row-{{ $dent }}">
                {{-- 这里原来有个「全」按钮（整行一键选）。去掉了：它写着「全」、
                     位置在象限外角，看着像管这个区，实际只管同一行的乳牙 ——
                     成年患者点它选中的是一排本来不存在的牙。改成按住拖过一片牙，
                     与 Open Dental 一致，少一个要解释的控件。见 tooth_pad.js 的
                     applyTooth()。 --}}
                {{-- 右侧象限的数组本身就是由外向内写的（18…11 / 55…51），
                     直接铺就是「最外侧在最左、门牙紧挨中线」—— 与牙位图的画法
                     一致。不要再 reverse，那会把 11 甩到最外侧去。 --}}
                <span class="tg-quad">
                    @foreach($quadrants[$pair[0]][$dent] as $t)
                        <span class="tg-t {{ $dent === 'milk' ? 'tg-milk' : '' }}"
                              data-tooth="{{ $t }}"
                              @if($onclick) onclick="{{ $onclick }}({{ $t }})" @endif
                              title="{{ $t }}">{{ $symbol($t) }}</span>
                    @endforeach
                </span>

                <span class="tg-mid"></span>

                <span class="tg-quad">
                    @foreach($quadrants[$pair[1]][$dent] as $t)
                        <span class="tg-t {{ $dent === 'milk' ? 'tg-milk' : '' }}"
                              data-tooth="{{ $t }}"
                              @if($onclick) onclick="{{ $onclick }}({{ $t }})" @endif
                              title="{{ $t }}">{{ $symbol($t) }}</span>
                    @endforeach
                </span>
            </div>
        @endforeach

        @if($half === 'upper')
            <div class="tg-midline">
                <span>{{ __('odontogram.right_abbr') }}</span>
                <span class="tg-midline-rule"></span>
                <span>{{ __('odontogram.left_abbr') }}</span>
            </div>
        @endif
    @endforeach
</div>
