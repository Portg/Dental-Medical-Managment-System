<?php

namespace Tests\Feature;

use Tests\TestCase;

class BreadcrumbNavigationTest extends TestCase
{
    public function test_layout_exposes_page_actions_to_the_global_breadcrumb_generator(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $script = file_get_contents(public_path('js/breadcrumb-auto.js'));

        $this->assertStringContainsString('data-breadcrumb-current', $layout);
        $this->assertStringContainsString("__('common.add')", $layout);
        $this->assertStringContainsString("__('common.edit')", $layout);
        $this->assertStringContainsString("__('common.details')", $layout);
        $this->assertStringContainsString("filemtime(public_path('js/breadcrumb-auto.js'))", $layout);

        $this->assertStringContainsString('inferActionName', $script);
        $this->assertStringContainsString('currentPath !== menuInfo.currentPath', $script);
        $this->assertStringContainsString('menuInfo.currentUrl', $script);
        $this->assertStringContainsString('pathMatches(currentPath, subPath)', $script);
    }

    public function test_medical_case_pages_declare_their_specific_breadcrumb_action(): void
    {
        $edit = file_get_contents(resource_path('views/medical_cases/edit.blade.php'));
        $show = file_get_contents(resource_path('views/medical_cases/show.blade.php'));

        $this->assertStringContainsString("__('medical_cases.add_case')", $edit);
        $this->assertStringContainsString("__('medical_cases.edit_case')", $edit);
        $this->assertStringContainsString("@section('page_title'", $edit);
        $this->assertStringContainsString("@section('page_title', __('medical_cases.view_case'))", $show);
    }
}
