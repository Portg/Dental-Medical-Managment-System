<?php

namespace Tests\Feature\Api;

use App\Appointment;
use App\Branch;
use App\Lab;
use App\LabCase;
use App\LabCaseItem;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class LabCaseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
    private Lab $lab;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch     = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole  = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $this->appointment = Appointment::create([
            'start_date'        => now()->addDay()->format('Y-m-d'),
            'end_date'          => now()->addDay()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->addDay()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->lab = Lab::create([
            'name'                => '上海义齿加工厂',
            'contact'             => '李经理',
            'phone'               => '021-12345678',
            'address'             => '上海市浦东新区',
            'specialties'         => '氧化锆,全瓷',
            'avg_turnaround_days' => 7,
            'is_active'           => true,
            '_who_added'          => $this->admin->id,
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validCaseData(array $overrides = []): array
    {
        return array_merge([
            'patient_id'           => $this->patient->id,
            'doctor_id'            => $this->doctor->id,
            'lab_id'               => $this->lab->id,
            'prosthesis_type'      => 'crown',
            'material'             => 'zirconia',
            'color_shade'          => 'A2',
            'teeth_positions'      => ['11', '12'],
            'expected_return_date' => now()->addDays(7)->format('Y-m-d'),
            'lab_fee'              => 500,
            'patient_charge'       => 2000,
        ], $overrides);
    }

    // ─── Labs CRUD ──────────────────────────────────────────────

    public function test_list_labs(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/labs');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    public function test_create_lab(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/labs', [
                'name'                => '北京精密义齿',
                'contact'             => '王工',
                'phone'               => '010-87654321',
                'avg_turnaround_days' => 5,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('labs', ['name' => '北京精密义齿']);
    }

    public function test_show_lab(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/labs/{$this->lab->id}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.name', '上海义齿加工厂');
    }

    public function test_update_lab(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/labs/{$this->lab->id}", [
                'name'  => '上海精密义齿加工',
                'phone' => '021-99999999',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('labs', [
            'id'   => $this->lab->id,
            'name' => '上海精密义齿加工',
        ]);
    }

    public function test_delete_lab(): void
    {
        $emptyLab = Lab::create([
            'name'       => '测试技工厂',
            'is_active'  => true,
            '_who_added' => $this->admin->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/labs/{$emptyLab->id}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('labs', ['id' => $emptyLab->id]);
    }

    // ─── Lab Cases CRUD ─────────────────────────────────────────

    public function test_create_lab_case(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data' => ['id', 'lab_case_no', 'prosthesis_type', 'status']]);

        $this->assertDatabaseHas('lab_cases', [
            'patient_id' => $this->patient->id,
            'status'     => 'pending',
        ]);

        // 修复体信息挂在明细表上（2026_03_06 迁移把这几列从 lab_cases 移走了）
        $this->assertDatabaseHas('lab_case_items', [
            'lab_case_id'     => $response->json('data.id'),
            'prosthesis_type' => 'crown',
            'material'        => 'zirconia',
        ]);
    }

    public function test_create_lab_case_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    public function test_list_lab_cases(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/lab-cases');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    public function test_show_lab_case(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $caseId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/lab-cases/{$caseId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $caseId)
                 ->assertJsonPath('data.prosthesis_type', 'crown');
    }

    public function test_update_lab_case(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $caseId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/lab-cases/{$caseId}", [
                'material'       => 'emax',
                'patient_charge' => 2500,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lab_cases', [
            'id'             => $caseId,
            'patient_charge' => 2500,
        ]);

        // 只传 material 时 prosthesis_type 应保留原值，不被冲掉
        $this->assertDatabaseHas('lab_case_items', [
            'lab_case_id'     => $caseId,
            'prosthesis_type' => 'crown',
            'material'        => 'emax',
            'deleted_at'      => null,
        ]);
    }

    public function test_update_with_flat_fields_keeps_other_items(): void
    {
        // 平铺字段只描述一件，但单子可挂最多 4 件。
        // updateLabCase() 是"按传入列表对齐明细"的语义——若 extractItems() 只回传第一件，
        // 第 2、3 件会被直接删掉，而客户端只是想改个 material。
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData([
                'items' => [
                    ['prosthesis_type' => 'crown',  'material' => 'zirconia', 'teeth_positions' => ['11']],
                    ['prosthesis_type' => 'bridge', 'material' => 'pfm',      'teeth_positions' => ['21']],
                    ['prosthesis_type' => 'veneer', 'material' => 'emax',     'teeth_positions' => ['31']],
                ],
            ]));

        $caseId = $create->json('data.id');
        $this->assertCount(3, LabCaseItem::where('lab_case_id', $caseId)->get());

        $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/lab-cases/{$caseId}", ['material' => 'gold'])
            ->assertOk();

        $items = LabCaseItem::where('lab_case_id', $caseId)->orderBy('sort_order')->get();

        $this->assertCount(3, $items, '只改平铺 material 不应删掉其余明细');
        // 平铺字段只并进第一件
        $this->assertSame('gold', $items[0]->material);
        $this->assertSame('crown', $items[0]->prosthesis_type, 'prosthesis_type 未传时应保留原值');
        // 其余两件原样保留
        $this->assertSame('bridge', $items[1]->prosthesis_type);
        $this->assertSame('pfm', $items[1]->material);
        $this->assertSame('veneer', $items[2]->prosthesis_type);
        $this->assertSame('emax', $items[2]->material);
    }

    public function test_update_items_reuses_rows_instead_of_recreating(): void
    {
        // 明细按 sort_order 就位复用：id 不漂移，也不堆积软删除的历史行。
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData([
                'items' => [
                    ['prosthesis_type' => 'crown',  'material' => 'zirconia'],
                    ['prosthesis_type' => 'bridge', 'material' => 'pfm'],
                ],
            ]));

        $caseId  = $create->json('data.id');
        $itemIds = LabCaseItem::where('lab_case_id', $caseId)->orderBy('sort_order')->pluck('id')->all();

        $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/lab-cases/{$caseId}", [
                'items' => [
                    ['prosthesis_type' => 'crown', 'material' => 'gold'],
                ],
            ])
            ->assertOk();

        $remaining = LabCaseItem::where('lab_case_id', $caseId)->get();

        $this->assertCount(1, $remaining);
        $this->assertSame($itemIds[0], $remaining->first()->id, '第一件应就地更新而不是重建');
        $this->assertSame('gold', $remaining->first()->material);
        // 缩短列表时多余的那件才被软删
        $this->assertSoftDeleted('lab_case_items', ['id' => $itemIds[1]]);
        // 没有额外堆积：全表（含软删）仍只有当初的 2 行
        $this->assertCount(2, LabCaseItem::withTrashed()->where('lab_case_id', $caseId)->get());
    }

    public function test_update_items_clears_omitted_fields(): void
    {
        // 明细就位复用会保留行上的旧值。调用方"清空某字段"的表达方式是不传这个 key
        // （Web 端 LabCaseController 在 teeth_positions 为空时就是不放进数组），
        // 所以托管列必须被补齐成 null，否则清空会静默失效。
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData([
                'items' => [
                    ['prosthesis_type' => 'crown', 'material' => 'zirconia', 'teeth_positions' => ['11', '12']],
                ],
            ]));

        $caseId = $create->json('data.id');

        $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/lab-cases/{$caseId}", [
                'items' => [
                    ['prosthesis_type' => 'crown'],
                ],
            ])
            ->assertOk();

        $item = LabCaseItem::where('lab_case_id', $caseId)->first();

        $this->assertNull($item->teeth_positions, '未传的 teeth_positions 应被清空而不是沿用旧值');
        $this->assertNull($item->material);
    }

    public function test_nested_teeth_positions_are_rejected_not_stored(): void
    {
        // teeth_positions 是 json 列，读取端按扁平字符串数组渲染；
        // 嵌套结构原样落库会让 Blade / 前端 JS 拿到无法处理的数据。
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData([
                'teeth_positions' => ['11', ['21', '22']],
            ]));

        $response->assertStatus(201);

        $item = LabCaseItem::where('lab_case_id', $response->json('data.id'))->first();

        $this->assertSame(['11'], $item->teeth_positions);
    }

    public function test_delete_lab_case(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $caseId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/lab-cases/{$caseId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('lab_cases', ['id' => $caseId]);
    }

    // ─── Status Flow ────────────────────────────────────────────

    public function test_update_status_to_sent(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $caseId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/lab-cases/{$caseId}/status", [
                'status' => 'sent',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lab_cases', [
            'id'     => $caseId,
            'status' => 'sent',
        ]);
    }

    public function test_update_status_rework_requires_reason(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $caseId = $create->json('data.id');

        // First move to sent
        $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/lab-cases/{$caseId}/status", ['status' => 'sent']);

        // Rework with reason
        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/lab-cases/{$caseId}/status", [
                'status'        => 'rework',
                'rework_reason' => '颜色不匹配',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lab_cases', [
            'id'            => $caseId,
            'status'        => 'rework',
            'rework_count'  => 1,
            'rework_reason' => '颜色不匹配',
        ]);
    }

    // ─── Special Endpoints ──────────────────────────────────────

    public function test_overdue_cases(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/lab-cases/overdue');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    public function test_patient_cases(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/lab-cases', $this->validCaseData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/lab-cases/patient/{$this->patient->id}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    public function test_statistics(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/lab-cases/statistics');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data' => ['total', 'active', 'completed', 'overdue']]);
    }

    public function test_options(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/lab-cases/options');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data' => ['prosthesis_types', 'materials', 'statuses']]);
    }

    // ─── Auth Guard ─────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/lab-cases')->assertStatus(401);
        $this->getJson('/api/v1/labs')->assertStatus(401);
    }
}
