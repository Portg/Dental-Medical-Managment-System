<?php

namespace Tests\Feature;

use App\Branch;
use App\ClinicDisinfectionRecord;
use App\EquipmentMaintenanceRecord;
use App\MedicalWasteHandoverRecord;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicAffairsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private User $manager;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Clinic Affairs Branch', 'is_active' => true]);
        $manageRole = Role::create(['name' => 'Clinic Affairs Manager', 'slug' => 'clinic-affairs-manager']);
        $viewerRole = Role::create(['name' => 'Clinic Affairs Viewer', 'slug' => 'clinic-affairs-viewer']);

        $viewPermission = Permission::firstOrCreate(
            ['slug' => 'view-clinic-affairs'],
            ['name' => 'View Clinic Affairs', 'module' => 'Clinic Affairs']
        );
        $managePermission = Permission::firstOrCreate(
            ['slug' => 'manage-clinic-affairs'],
            ['name' => 'Manage Clinic Affairs', 'module' => 'Clinic Affairs']
        );

        RolePermission::create(['role_id' => $manageRole->id, 'permission_id' => $viewPermission->id]);
        RolePermission::create(['role_id' => $manageRole->id, 'permission_id' => $managePermission->id]);
        RolePermission::create(['role_id' => $viewerRole->id, 'permission_id' => $viewPermission->id]);

        $this->manager = User::factory()->create([
            'role_id' => $manageRole->id,
            'branch_id' => $this->branch->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->viewer = User::factory()->create([
            'role_id' => $viewerRole->id,
            'branch_id' => $this->branch->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_all_three_workspace_pages_are_available_to_viewer(): void
    {
        $this->actingAs($this->viewer)->get('/clinic-affairs/disinfection')->assertOk();
        $this->actingAs($this->viewer)->get('/clinic-affairs/equipment-maintenance')->assertOk();
        $this->actingAs($this->viewer)->get('/clinic-affairs/medical-waste')->assertOk();
    }

    public function test_viewer_cannot_create_compliance_records(): void
    {
        $this->actingAs($this->viewer)->postJson('/clinic-affairs/disinfection', [
            'area' => '诊室 1',
            'check_type' => 'clinical_surface',
            'performed_at' => now()->format('Y-m-d H:i:s'),
            'result' => 'pass',
        ])->assertForbidden();
    }

    public function test_manager_can_create_all_three_record_types(): void
    {
        $this->actingAs($this->manager)->postJson('/clinic-affairs/disinfection', [
            'area' => '诊室 1',
            'check_type' => 'waterline',
            'disinfectant' => '水路处理剂',
            'performed_at' => now()->format('Y-m-d H:i:s'),
            'result' => 'pass',
        ])->assertOk()->assertJson(['status' => 1]);

        $this->actingAs($this->manager)->postJson('/clinic-affairs/equipment-maintenance', [
            'equipment_code' => 'AUTO-001',
            'equipment_name' => '压力蒸汽灭菌器',
            'category' => 'sterilizer',
            'maintenance_type' => 'preventive',
            'performed_at' => now()->format('Y-m-d H:i:s'),
            'next_due_at' => now()->addMonths(6)->format('Y-m-d'),
            'result' => 'normal',
        ])->assertOk()->assertJson(['status' => 1]);

        $this->actingAs($this->manager)->postJson('/clinic-affairs/medical-waste', [
            'waste_type' => 'sharps',
            'weight_kg' => 1.25,
            'package_count' => 2,
            'handed_over_at' => now()->format('Y-m-d H:i:s'),
            'receiver_name' => '处置单位接收员',
            'manifest_no' => 'MW-20260811-001',
        ])->assertOk()->assertJson([
            'status' => 1,
            'stats' => ['monthly_waste_kg' => 1.25],
        ]);

        $this->assertDatabaseHas('clinic_disinfection_records', [
            'branch_id' => $this->branch->id,
            'operator_id' => $this->manager->id,
            'check_type' => 'waterline',
        ]);
        $this->assertSame(1, EquipmentMaintenanceRecord::count());
        $this->assertSame(1, MedicalWasteHandoverRecord::count());
    }

    public function test_issue_requires_corrective_action(): void
    {
        $this->actingAs($this->manager)->postJson('/clinic-affairs/disinfection', [
            'area' => '诊室 2',
            'check_type' => 'clinical_surface',
            'performed_at' => now()->format('Y-m-d H:i:s'),
            'result' => 'issue',
        ])->assertStatus(422)->assertJsonValidationErrors('corrective_action');
    }

    public function test_reviewed_disinfection_record_is_locked(): void
    {
        $record = ClinicDisinfectionRecord::create([
            'branch_id' => $this->branch->id,
            'area' => '消毒室',
            'check_type' => 'housekeeping',
            'performed_at' => now(),
            'result' => 'pass',
            'operator_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->postJson('/clinic-affairs/disinfection/' . $record->id . '/review')
            ->assertOk()->assertJson(['status' => 1]);

        $this->actingAs($this->manager)->putJson('/clinic-affairs/disinfection/' . $record->id, [
            'area' => '修改后的区域',
            'check_type' => 'housekeeping',
            'performed_at' => now()->format('Y-m-d H:i:s'),
            'result' => 'pass',
        ])->assertStatus(409);

        $this->actingAs($this->manager)
            ->deleteJson('/clinic-affairs/disinfection/' . $record->id)
            ->assertStatus(409);
    }

    public function test_records_are_scoped_to_the_users_branch(): void
    {
        $otherBranch = Branch::create(['name' => 'Other Branch', 'is_active' => true]);
        ClinicDisinfectionRecord::create([
            'branch_id' => $otherBranch->id,
            'area' => '其他门店',
            'check_type' => 'housekeeping',
            'performed_at' => now(),
            'result' => 'pass',
            'operator_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson('/clinic-affairs/disinfection/data?draw=1&start=0&length=10');

        $response->assertOk();
        $this->assertSame(0, $response->json('recordsTotal'));
    }

    /**
     * 导出必须跟随页面上的筛选。原来一律全量导出：页面筛「合格」、导出来还是全部，
     * 用户拿到的表和屏幕上看到的对不上，是最容易酿成误判的那种错。
     */
    public function test_export_honours_the_active_filter(): void
    {
        $this->seedDisinfection('pass', '合格区域');
        $this->seedDisinfection('issue', '异常区域');

        $all = $this->exportRows();
        $this->assertCount(2, $all, '不带筛选应导出全部');

        $onlyIssues = $this->exportRows(['result' => 'issue']);
        $this->assertCount(1, $onlyIssues);
        $this->assertStringContainsString('异常区域', $onlyIssues[0]);
        $this->assertStringNotContainsString('合格区域', implode('', $onlyIssues));
    }

    /**
     * 异常记录不带整改措施、复核过的不带复核人，这份表拿去应付检查等于白导。
     */
    public function test_export_carries_the_fields_an_inspector_asks_for(): void
    {
        $record = $this->seedDisinfection('issue', '手术间', ['corrective_action' => '已重新擦拭并复测']);
        $this->actingAs($this->manager)->postJson('/clinic-affairs/disinfection/' . $record->id . '/review');

        $csv = $this->exportRaw();

        foreach ([__('clinic_affairs.corrective_action'), __('clinic_affairs.reviewer'), __('clinic_affairs.reviewed_at')] as $header) {
            $this->assertStringContainsString($header, $csv, "导出缺少「{$header}」列");
        }
        $this->assertStringContainsString('已重新擦拭并复测', $csv);
        $this->assertStringContainsString($this->manager->full_name, $csv);
    }

    /**
     * 登记人复核自己填的记录不拦（单护士诊所会被卡死），但必须在列表上标出来。
     */
    public function test_self_review_is_allowed_but_flagged(): void
    {
        $own = $this->seedDisinfection('pass', '自审记录', ['operator_id' => $this->manager->id]);
        $this->actingAs($this->manager)->postJson('/clinic-affairs/disinfection/' . $own->id . '/review')->assertOk();

        $badge = $this->actingAs($this->manager)
            ->getJson('/clinic-affairs/disinfection/data?draw=1&start=0&length=10')
            ->json('data.0.review_status');

        $this->assertStringContainsString('本人', $badge, '自审记录必须标注「本人」');
        $this->assertStringContainsString($this->manager->full_name, $badge, '徽章上要能看到复核人');
        $this->assertStringContainsString('label-info', $badge, '自审用 info 色：既不能撞「待复核」的 warning，也不能混进他人复核的 success');
    }

    public function test_review_badge_shows_the_reviewer_when_someone_else_reviews(): void
    {
        $record = $this->seedDisinfection('pass', '他人复核', ['operator_id' => $this->viewer->id]);
        $this->actingAs($this->manager)->postJson('/clinic-affairs/disinfection/' . $record->id . '/review')->assertOk();

        $badge = $this->actingAs($this->manager)
            ->getJson('/clinic-affairs/disinfection/data?draw=1&start=0&length=10')
            ->json('data.0.review_status');

        $this->assertStringContainsString($this->manager->full_name, $badge);
        $this->assertStringNotContainsString('本人', $badge);
        $this->assertStringContainsString('label-success', $badge);
    }

    private function seedDisinfection(string $result, string $area, array $overrides = []): ClinicDisinfectionRecord
    {
        return ClinicDisinfectionRecord::create(array_merge([
            'branch_id' => $this->branch->id,
            'area' => $area,
            'check_type' => 'clinical_surface',
            'performed_at' => now(),
            'result' => $result,
            'corrective_action' => $result === 'issue' ? '已处理' : null,
            'operator_id' => $this->manager->id,
        ], $overrides));
    }

    private function exportRaw(array $query = []): string
    {
        $response = $this->actingAs($this->manager)
            ->get('/clinic-affairs/disinfection-export' . ($query ? '?' . http_build_query($query) : ''));

        $response->assertOk();

        return $response->streamedContent();
    }

    /** 返回去掉表头和空行之后的数据行 */
    private function exportRows(array $query = []): array
    {
        $lines = array_filter(explode("\n", trim($this->exportRaw($query))));
        array_shift($lines);

        return array_values($lines);
    }

    public function test_data_table_returns_row_index_and_actions_for_manager(): void
    {
        ClinicDisinfectionRecord::create([
            'branch_id' => $this->branch->id,
            'area' => '诊室 3',
            'check_type' => 'clinical_surface',
            'performed_at' => now(),
            'result' => 'pass',
            'operator_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson('/clinic-affairs/disinfection/data?draw=1&start=0&length=10');

        $response->assertOk();
        $this->assertSame(1, $response->json('data.0.DT_RowIndex'));
        $this->assertStringContainsString('ClinicAffairs.edit', $response->json('data.0.action'));
    }
}
