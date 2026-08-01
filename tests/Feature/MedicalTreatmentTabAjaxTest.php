<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 诊疗页处方 / 牙科账单 DataTable Ajax 门禁与路由对齐。
 *
 * 历史缺陷：
 * - prescriptions.js 误请求 /prescriptions/{id}（show），应走 /prescriptions/appointment/{id}
 * - InvoiceItemController 整控制器 edit-invoices，医生仅有 view-invoices 时列表 403
 */
class MedicalTreatmentTabAjaxTest extends TestCase
{
    public function test_appointment_invoice_items_is_behind_view_invoices_not_edit(): void
    {
        $gates = $this->gatesFor('InvoiceItemController@appointmentInvoiceItems');

        $this->assertContains(
            'view-invoices',
            $gates,
            'appointmentInvoiceItems 应对齐诊疗页 Tab 的 view-invoices'
        );
        $this->assertNotContains(
            'edit-invoices',
            $gates,
            '列表只读，不应再要求 edit-invoices（医生等角色会 DataTables 403）'
        );
    }

    public function test_invoice_item_mutations_still_require_edit_invoices(): void
    {
        foreach (['edit', 'update', 'destroy'] as $method) {
            $gates = $this->gatesFor('InvoiceItemController@' . $method);
            $this->assertContains(
                'edit-invoices',
                $gates,
                "InvoiceItemController@{$method} 仍应要求 edit-invoices"
            );
        }
    }

    public function test_prescription_appointment_list_route_exists(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/prescriptions/appointment/1', 'GET')
        );

        $this->assertSame(
            'App\Http\Controllers\PrescriptionController@index',
            $route->getActionName()
        );
    }

    public function test_prescriptions_id_route_is_show_not_datatable_list(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/prescriptions/1', 'GET')
        );

        $this->assertSame(
            'App\Http\Controllers\PrescriptionController@show',
            $route->getActionName(),
            'DataTable 不得再用 /prescriptions/{id}，该路由是详情 show'
        );
    }

    /**
     * @return list<string>
     */
    private function gatesFor(string $action): array
    {
        $controller = 'App\\Http\\Controllers\\' . explode('@', $action)[0];
        $method = explode('@', $action)[1];

        $instance = app($controller);
        $middleware = collect($instance->getMiddleware())
            ->filter(function (array $m) use ($method) {
                $only = $m['options']['only'] ?? null;
                $except = $m['options']['except'] ?? null;
                if ($only !== null && !in_array($method, (array) $only, true)) {
                    return false;
                }
                if ($except !== null && in_array($method, (array) $except, true)) {
                    return false;
                }
                return true;
            })
            ->pluck('middleware')
            ->all();

        $gates = [];
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'can:')) {
                $gates[] = substr($m, 4);
            }
        }
        return $gates;
    }
}
