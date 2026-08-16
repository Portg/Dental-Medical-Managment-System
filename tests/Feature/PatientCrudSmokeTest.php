<?php

namespace Tests\Feature;

use App\Branch;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validPatientData(array $overrides = []): array
    {
        return array_merge([
            'surname'   => '张',
            'othername' => '三',
            'gender'    => 'Male',
            'telephone' => '13800138000',
            'phone_no'  => '13800138000',
            'email'     => 'zhangsan@example.com',
        ], $overrides);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_patient_with_valid_data(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.surname', '张')
                 ->assertJsonPath('data.othername', '三');

        $this->assertDatabaseHas('patients', [
            'surname'   => '张',
            'othername' => '三',
        ]);
    }

    public function test_patient_resource_exposes_dob_alongside_date_of_birth(): void
    {
        // dob 是表单字段名，写入时映射到 date_of_birth（PatientService::OPTIONAL_FIELDS）。
        // 两个 key 都要出现在响应里且同值：dob 是 v1 的历史契约，不能因为它曾经恒为 null 就删掉。
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData(['dob' => '1990-05-15']));

        $response->assertStatus(201)
                 ->assertJsonPath('data.dob', '1990-05-15')
                 ->assertJsonPath('data.date_of_birth', '1990-05-15');

        $this->assertDatabaseHas('patients', ['date_of_birth' => '1990-05-15']);
    }

    public function test_create_patient_validation_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_patients_returns_paginated(): void
    {
        // Create a patient first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/patients');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'message', 'meta' => ['current_page', 'total']]);
    }

    public function test_list_patients_with_search(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/patients?search=张');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_patient_returns_detail(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $patientId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/patients/{$patientId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.patient.surname', '张')
                 ->assertJsonStructure(['success', 'data' => ['patient', 'counts']]);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_patient(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $patientId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/patients/{$patientId}", $this->validPatientData([
                'surname'   => '李',
                'othername' => '四',
            ]));

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.surname', '李');

        $this->assertDatabaseHas('patients', [
            'id'        => $patientId,
            'surname'   => '李',
            'othername' => '四',
        ]);
    }

    // ─── Delete (soft) ─────────────────────────────────────────────

    public function test_delete_patient_soft_deletes(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $patientId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/patients/{$patientId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('patients', ['id' => $patientId]);
    }

    // ─── Search endpoint ───────────────────────────────────────────

    public function test_search_patients_by_name(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/patients/search?q=张');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── 建档日期（补录）────────────────────────────────────────────

    /**
     * 建档日期要能补录。
     *
     * 此前患者表只有 date_of_birth 和时间戳，「建档日期」用的就是 created_at ——
     * 新建时自动取当天、改不了。诊所把纸质档案或旧系统的患者补录进来时全部记成
     * 录入当天，而患者列表的日期筛选和新增患者报表都按这一列算，
     * 「本月新增」里就混进了二十年前建档的老患者。
     */
    public function test_registered_at_can_be_back_filled(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData(['registered_at' => '2015-03-08']))
            ->assertStatus(201);

        $this->assertDatabaseHas('patients', ['registered_at' => '2015-03-08']);
    }

    public function test_registered_at_defaults_to_today(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData())
            ->assertStatus(201);

        $this->assertDatabaseHas('patients', ['registered_at' => now()->toDateString()]);
    }

    /**
     * created_at 仍然是纯审计时间戳 —— 补录不该把「这条记录什么时候进的系统」也改掉。
     */
    public function test_back_filling_does_not_touch_created_at(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/patients', $this->validPatientData(['registered_at' => '2015-03-08']))
            ->assertStatus(201);

        $patient = \App\Patient::where('registered_at', '2015-03-08')->firstOrFail();

        $this->assertSame(
            now()->toDateString(),
            $patient->created_at->toDateString(),
            'created_at 应当还是录入当天'
        );
    }

    // ─── Auth guard ────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_patients(): void
    {
        $this->getJson('/api/v1/patients')->assertStatus(401);
        $this->postJson('/api/v1/patients', $this->validPatientData())->assertStatus(401);
    }
}
