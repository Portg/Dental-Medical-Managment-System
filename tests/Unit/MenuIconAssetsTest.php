<?php

namespace Tests\Unit;

use Tests\TestCase;

class MenuIconAssetsTest extends TestCase
{
    public function testBackendLayoutLoadsMenuIconStylesFromResolvableAssetPaths(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString(
            "backend/assets/global/plugins/simple-line-icons/simple-line-icons.min.css",
            $layout
        );
        $this->assertStringContainsString(
            "backend/assets/global/plugins/font-awesome/css/all.min.css",
            $layout
        );
        $this->assertStringContainsString(
            "backend/assets/global/plugins/font-awesome/css/v4-shims.min.css",
            $layout
        );

        $this->assertFileExists(public_path(
            'backend/assets/global/plugins/simple-line-icons/fonts/Simple-Line-Icons.woff'
        ));
        $this->assertFileExists(public_path(
            'backend/assets/global/plugins/font-awesome/webfonts/fa-solid-900.woff2'
        ));
    }

    /**
     * 这三行 <link> 能救回图标，唯一的原因是它们排在 backend-bundle.css **之后**。
     *
     * 已提交的 backend-bundle.css 里还留着一份 simple-line-icons 的 @font-face，
     * 而它的字体是相对路径 url(fonts/…)：从 /css/ 解析成 /css/fonts/… → 404。
     * 两份 @font-face 同名时后定义的生效，所以顺序一颠倒，图标立刻变回空白方块，
     * 而且页面不会报任何错——只有肉眼能发现。这条断言就是替肉眼守着。
     */
    public function testIconStylesAreLinkedAfterTheBundleSoTheirFontFacesWin(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $bundlePosition = strpos($layout, 'css/backend-bundle.css');
        $this->assertNotFalse($bundlePosition, 'backend-bundle.css 的引用不见了');

        foreach ([
            'simple-line-icons/simple-line-icons.min.css',
            'font-awesome/css/all.min.css',
            'font-awesome/css/v4-shims.min.css',
        ] as $iconStylesheet) {
            $iconPosition = strpos($layout, $iconStylesheet);
            $this->assertNotFalse($iconPosition, $iconStylesheet . ' 的引用不见了');
            $this->assertGreaterThan(
                $bundlePosition,
                $iconPosition,
                $iconStylesheet . ' 必须排在 backend-bundle.css 之后，否则 bundle 里那份坏的 @font-face 会赢'
            );
        }
    }

    /**
     * 反向守一半：图标 CSS 不许再被打进 backend-bundle.css。
     *
     * mix.styles 只做拼接、不重写 url()，产物落在 public/css/ 下，相对字体路径
     * 必然解析错。而且 bundle 里原来收的是 fontawesome.min.css —— 那份只有基础
     * 样式、不含 @font-face，是全站 fa fa-* 一直没有字体的根本原因。
     */
    public function testIconStylesheetsAreNotBundledByMix(): void
    {
        $mixConfig = file_get_contents(base_path('webpack.mix.js'));

        // 只看未被注释掉的行，避免把说明用的注释误判成配置
        $activeLines = array_filter(
            preg_split('/\R/', $mixConfig),
            static fn (string $line): bool => ! str_starts_with(ltrim($line), '//')
        );
        $activeConfig = implode("\n", $activeLines);

        foreach ([
            'simple-line-icons/simple-line-icons.min.css',
            'font-awesome/css/fontawesome.min.css',
        ] as $mustNotBeBundled) {
            $this->assertStringNotContainsString(
                $mustNotBeBundled,
                $activeConfig,
                $mustNotBeBundled . ' 又被打进 bundle 了：字体相对路径会解析到 /css/ 下，图标会变空白方块'
            );
        }
    }

    public function testSidebarExpandArrowsUseTheBundledFontAwesomeFamily(): void
    {
        $menuNode = file_get_contents(resource_path('views/partials/_menu_node.blade.php'));
        $theme = file_get_contents(public_path('css/theme-purple.css'));

        $this->assertStringContainsString('<span class="arrow"></span>', $menuNode);
        $this->assertStringContainsString(
            '.page-sidebar .page-sidebar-menu li > a > .arrow:before',
            $theme
        );
        $this->assertStringContainsString('font-family: "Font Awesome 5 Free" !important;', $theme);
        $this->assertStringContainsString('font-weight: 900 !important;', $theme);
    }
}
