<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 锁定「页面可进入、但其 AJAX 依赖 403」这一类缺陷。
 *
 * 病根始终一样：控制器构造函数里一条 $this->middleware('can:manage-xxx') 不带
 * ->only()，把只读的下拉查找方法和增删改一起圈进了写权限。页面本身能打开，
 * 页面里的 select2 却恒为空——超管测不出来，必须按角色分别请求才会暴露。
 *
 * 本测试断言的是路由上实际生效的门禁，而不是逐角色发 HTTP 请求：门禁挂错是根因，
 * 断言根因既精确又不依赖数据库播种。
 *
 * 历史：ae2c9f2（/search-doctor 误挂 view-users）、795adab 与 0cf73d0（本批 10 个端点）。
 */
class LookupEndpointPermissionTest extends TestCase
{
    /**
     * 只读查找端点 => 绝不允许再被挂上的写权限。
     *
     * 这些端点只返回 id/名称一类的名录数据，供各业务页面的下拉查找使用；
     * 使用它们的角色（医生、护士、前台、库管）并不持有对应的管理权限。
     */
    private const READ_ONLY_LOOKUPS = [
        'RoleController@filterRoles'                 => 'manage-roles',
        'MedicalServiceController@filterServices'    => 'manage-medical-services',
        'InsuranceCompaniesController@filterCompanies' => 'manage-insurance',
        'SelfAccountController@filterAccounts'       => 'manage-accounting',
        'SupplierController@filterSuppliers'         => 'manage-inventory',
        'DictItemController@list'                    => 'manage-patient-settings',
        'PatientSourceController@list'               => 'manage-patient-settings',
        'PatientTagController@list'                  => 'manage-patient-settings',
        // ae2c9f2：预约抽屉的医生下拉，曾被 view-users 挡住
        'UsersController@filterDoctor'               => 'view-users',
    ];

    /**
     * 病历书写页专用的查找端点 => 必须由 edit-patients 把关。
     *
     * 模板浮层与短语浮层绑定在 .template-enabled / .phrase-enabled 输入框上，
     * 这两个 class 只出现在诊断、治疗计划、病程记录三个书写页，其准入正是
     * edit-patients。用 manage-medical-cases 把关会漏掉前台——前台能进书写页。
     */
    private const CLINICAL_LOOKUPS = [
        'MedicalTemplateController@search'         => 'edit-patients',
        'MedicalTemplateController@incrementUsage' => 'edit-patients',
        'QuickPhraseController@search'             => 'edit-patients',
    ];

    /**
     * 需要全量核查「非查找方法必须有门禁」的控制器。
     */
    private const GUARDED_CONTROLLERS = [
        'RoleController',
        'MedicalServiceController',
        'InsuranceCompaniesController',
        'SelfAccountController',
        'SupplierController',
        'DictItemController',
        'PatientSourceController',
        'PatientTagController',
        'QuickPhraseController',
        'MedicalTemplateController',
    ];

    // ─── 只读查找端点不得被写权限挡住 ────────────────────────────────

    public function test_read_only_lookups_are_not_behind_write_permissions(): void
    {
        foreach (self::READ_ONLY_LOOKUPS as $action => $forbiddenGate) {
            $gates = $this->gatesFor($action);

            $this->assertNotContains(
                $forbiddenGate,
                $gates,
                "{$action} 被挂在 '{$forbiddenGate}' 下。该端点只返回下拉查找用的名录数据，"
                . "而使用它的角色并不持有该管理权限——页面能打开，下拉却恒为空。"
                . "请在控制器构造函数中用 ->except() 把它移出该权限组。"
            );
        }
    }

    // ─── 病历书写页的查找端点由 edit-patients 把关 ───────────────────

    public function test_clinical_lookups_are_gated_by_edit_patients(): void
    {
        foreach (self::CLINICAL_LOOKUPS as $action => $expectedGate) {
            $gates = $this->gatesFor($action);

            $this->assertContains(
                $expectedGate,
                $gates,
                "{$action} 应由 '{$expectedGate}' 把关，实际为 [" . implode(', ', $gates) . "]。"
            );

            // 粒度过粗会漏掉前台：前台持有 edit-patients，但没有这两个权限
            foreach (['manage-medical-cases', 'manage-medical-services', 'manage-settings'] as $tooNarrow) {
                $this->assertNotContains(
                    $tooNarrow,
                    $gates,
                    "{$action} 仍叠加了 '{$tooNarrow}'。前台能进病历书写页却不持有该权限，"
                    . "叠加后会在敲字时拿不到模板/短语。"
                );
            }
        }
    }

    // ─── 除声明的查找端点外，其余方法必须仍有门禁 ────────────────────

    public function test_all_other_actions_remain_gated(): void
    {
        $openByDesign = array_map(
            fn ($a) => 'App\\Http\\Controllers\\' . $a,
            array_keys(self::READ_ONLY_LOOKUPS)
        );

        $checked = 0;

        foreach ($this->routesOfControllers(self::GUARDED_CONTROLLERS) as $action => $gates) {
            if (in_array($action, $openByDesign, true)) {
                continue;
            }

            $this->assertNotEmpty(
                $gates,
                "{$action} 没有任何权限中间件。放开只读查找端点时用 ->except() 误伤了它，"
                . "或新增方法时漏挂了门禁。"
            );
            $checked++;
        }

        // 防止匹配逻辑失效导致这个测试静默空跑。
        // 编写时这 10 个控制器共 96 个 action，减去 8 个声明放开的查找端点 = 88 个受检。
        // 阈值取 80：容得下正常的路由增删，但控制器命名空间变更之类的部分匹配失效会被抓住。
        $this->assertGreaterThanOrEqual(80, $checked, "受检方法数异常偏少（{$checked}），路由匹配逻辑可能已失效。");
    }

    // ─── 患者导出仍受 export-patients 保护 ───────────────────────────

    public function test_patient_export_remains_restricted(): void
    {
        $gates = $this->gatesFor('PatientController@exportPatients');

        $this->assertContains(
            'export-patients',
            $gates,
            '/export-patients 丢失了 export-patients 门禁。导出患者名单是敏感操作，'
            . '医生、护士、前台看不到该按钮是靠 Blade @can 隐藏的，服务端必须独立把关。'
        );
    }

    // ─── helpers ─────────────────────────────────────────────────────

    /**
     * 取某个控制器方法上实际生效的权限门禁（含构造函数注入的中间件）。
     */
    private function gatesFor(string $action): array
    {
        $full = 'App\\Http\\Controllers\\' . $action;

        foreach (app('router')->getRoutes() as $route) {
            if ($route->getActionName() === $full) {
                return $this->extractGates($route);
            }
        }

        $this->fail("找不到路由：{$full}（方法被删除或重命名？）");
    }

    /**
     * @param  string[]  $controllers
     * @return array<string, string[]>  action => gates
     */
    private function routesOfControllers(array $controllers): array
    {
        $out = [];

        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();

            foreach ($controllers as $c) {
                if (str_contains($action, '\\' . $c . '@')) {
                    $out[$action] = $this->extractGates($route);
                    break;
                }
            }
        }

        return $out;
    }

    private function extractGates(\Illuminate\Routing\Route $route): array
    {
        $gates = [];

        foreach ($route->gatherMiddleware() as $mw) {
            if (! is_string($mw)) {
                continue;
            }
            if (str_starts_with($mw, 'can:')) {
                $gates[] = explode(',', substr($mw, 4))[0];
            } elseif (str_contains($mw, 'Authorize:')) {
                $gates[] = explode(',', explode('Authorize:', $mw)[1])[0];
            }
        }

        return $gates;
    }
}
