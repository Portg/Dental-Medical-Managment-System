<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 「控制器返回了一个不存在的 Blade」这类缺陷，跑测试、看首页都发现不了 —— 只有真的
 * 打开那个页面才炸 View not found。
 *
 * 最贵的一次是退费：refunds_create.js 在提交成功后 1.5 秒 window.location 跳到
 * /refunds/{id}，而 RefundController@show 返回的 refunds.show 根本不存在。也就是说
 * 每一笔无需审批的退费，钱扣了、单据建了，用户看到的是 500。
 *
 * BladeTemplatesCompileTest 保证的是「模板能编译成合法 PHP」，管不到「模板存不存在」。
 * 这条用例补上另一半：把路由表里每个控制器方法的 view()/loadView() 字面量扒出来，
 * 断言对应文件在磁盘上。
 *
 * 只查路由表里的方法 —— 够不着的死代码不该让这条用例常红。
 */
class RoutableViewsExistTest extends TestCase
{
    /**
     * @test
     *
     * 同一个根因的另一半：Route::resource 不加 ->only()/->except() 时，会把控制器
     * 根本没实现的 create/show/edit 也注册成路由。命中就是 BadMethodCallException，
     * 一样的 500，只是抛的位置更早。修这批时一次性收口了 44 条。
     */
    public function every_route_points_at_a_method_that_exists(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);

            if (! class_exists($class)) {
                $missing[] = strtoupper($route->methods()[0]) . " /{$route->uri()} → 类 {$class} 不存在";
                continue;
            }

            if (! method_exists($class, $method)) {
                $short = class_basename($class);
                $missing[] = strtoupper($route->methods()[0]) . " /{$route->uri()} → {$short}@{$method}() 不存在";
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($missing)),
            "以下路由命中后会直接 500（BadMethodCallException）—— "
            . "多半是 Route::resource 没用 ->only()/->except() 收口：\n"
            . implode("\n", array_unique($missing))
        );
    }

    /** @test */
    public function every_routable_controller_action_renders_an_existing_view(): void
    {
        $missing = [];

        foreach ($this->routableActions() as $action) {
            [$class, $method] = $action;

            foreach ($this->viewNamesIn($class, $method) as $view => $line) {
                if (view()->exists($view)) {
                    continue;
                }

                $short = class_basename($class);
                $missing[] = "{$short}@{$method}() (行 {$line}) → view('{$view}') 不存在";
            }
        }

        $this->assertSame(
            [],
            $missing,
            "以下路由命中后会直接 500（View not found）：\n" . implode("\n", $missing)
        );
    }

    /**
     * 路由表里所有指向控制器方法的 action，去重后返回 [[class, method], ...]。
     */
    private function routableActions(): array
    {
        $actions = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue; // 闭包路由
            }

            [$class, $method] = explode('@', $action, 2);

            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            $actions[$action] = [$class, $method];
        }

        return array_values($actions);
    }

    /**
     * 扫方法体源码里的 view('x') / loadView('x') 字面量。
     *
     * 只认字面量：变量拼出来的视图名静态查不了，那种情况本来也该在别处兜。
     *
     * @return array<string, int> 视图名 => 行号
     */
    private function viewNamesIn(string $class, string $method): array
    {
        $reflection = new ReflectionMethod($class, $method);

        $file = $reflection->getFileName();
        if ($file === false) {
            return [];
        }

        $start = $reflection->getStartLine();
        $body = implode('', array_slice(
            file($file),
            $start - 1,
            $reflection->getEndLine() - $start + 1
        ));

        if (! preg_match_all('/(?:loadView|(?<![\w>])view)\(\s*[\'"]([a-zA-Z0-9_.\-\/]+)[\'"]/', $body, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $views = [];

        foreach ($matches[1] as $match) {
            [$name, $offset] = $match;

            // 有的地方写 view('a/b')，Blade 两种分隔符都认，统一成点号再查
            $views[str_replace('/', '.', $name)] = $start + substr_count(substr($body, 0, $offset), "\n");
        }

        return $views;
    }
}
