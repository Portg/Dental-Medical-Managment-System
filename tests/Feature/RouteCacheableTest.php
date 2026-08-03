<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 路由必须可缓存。
 *
 * Laravel 要求路由名全局唯一。不缓存时它容忍重名（后注册的覆盖），所以重名
 * 可以长期潜伏；但 `artisan route:cache` 会抛 LogicException 并以退出码 1 失败，
 * 结果是**生产环境从来没有路由缓存**——每个请求都要重新注册全部路由，而且
 * route('xxx.index') 解析到哪一条取决于注册顺序，URL 生成是有歧义的。
 *
 * 这个坑之前真实发生过：api/v1 的 apiResource 与 web.php 的 resource 撞了
 * 68 个名字（14 个资源 × 各 CRUD 动作），装机日志里 route:cache 报错，
 * 而安装器没检查退出码、把这一步报成了 OK，一直没人发现。
 * 修法是给 API v1 挂 api.v1. 名字前缀（见 RouteServiceProvider::mapApiV1Routes）。
 */
class RouteCacheableTest extends TestCase
{
    public function test_no_duplicate_route_names(): void
    {
        $byName = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            // 组名前缀留下的光前缀（如 'api.v1.'）由框架按无名路由处理，
            // 见 AbstractRouteCollection::addToSymfonyRoutesCollection()
            if ($name === null || str_ends_with($name, '.')) {
                continue;
            }

            $byName[$name][] = $route->uri();
        }

        $duplicates = array_filter($byName, fn ($uris) => count($uris) > 1);

        $this->assertSame(
            [],
            $duplicates,
            "存在重复的路由名，route:cache 会失败：\n" . collect($duplicates)
                ->map(fn ($uris, $name) => "  {$name}: " . implode(' | ', $uris))
                ->implode("\n")
        );
    }

    public function test_routes_can_be_serialized_for_caching(): void
    {
        // 直接复现 route:cache 的核心动作：编译成 Symfony 路由集合。
        // 重名会在这里抛 LogicException，和 artisan route:cache 失败的路径一致。
        Route::getRoutes()->compile();

        $this->assertTrue(true, '路由可序列化');
    }
}
