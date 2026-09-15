<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\MedicalService;
use App\Patient;
use App\PatientTag;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 顶栏搜索信息卡：GET /search-patient?card=1 返回固定 DTO，
 * 含识别信息、标签、上次就诊、欠费（不对齐视频里没有的办事按钮）。
 */
class TopbarPatientSearchCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        foreach ([
            'view-patients', 'create-patients', 'create-appointments', 'view-appointments',
            'manage-medical-cases', 'create-invoices', 'view-invoices',
        ] as $slug) {
            $perm = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        }
        Cache::flush();

        $this->admin = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE,
            'surname' => '关',
            'othername' => '立亚',
            'password' => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'patient_no' => '1779001',
            'surname' => '孙',
            'othername' => '艳萍',
            'gender' => 'Female',
            'date_of_birth' => Carbon::now()->subYears(42)->format('Y-m-d'),
            'phone_no' => '13800123358',
            'member_balance' => 50,
            '_who_added' => $this->admin->id,
        ]);

        $tag = PatientTag::create(['name' => 'VIP', '_who_added' => $this->admin->id]);
        $this->patient->patientTags()->attach($tag->id);

        $service = MedicalService::create([
            'name' => '洗牙',
            'price' => 100,
            'is_active' => true,
            '_who_added' => $this->admin->id,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->admin->id,
            'service_id' => $service->id,
            'branch_id' => $branch->id,
            'start_date' => '2026-09-01',
            'start_time' => '10:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            '_who_added' => $this->admin->id,
        ]);

        Invoice::create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $appointment->id,
            'invoice_no' => 'INV-TOPBAR-1',
            'total_amount' => 200,
            'paid_amount' => 80,
            'outstanding_amount' => 120,
            'payment_status' => 'partial',
            '_who_added' => $this->admin->id,
        ]);

        $this->app->setLocale('zh-CN');
    }

    /** @test */
    public function card_search_returns_enriched_patient_dto(): void
    {
        $this->assertDatabaseHas('patients', ['id' => $this->patient->id, 'surname' => '孙']);

        $response = $this->actingAs($this->admin)
            ->getJson('/search-patient?' . http_build_query(['q' => '孙', 'card' => 1]));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data, 'response: ' . $response->getContent());

        $card = collect($data)->firstWhere('id', $this->patient->id);
        $this->assertNotNull($card);
        $this->assertSame('孙艳萍', $card['full_name']);
        $this->assertSame('女', $card['gender']);
        $this->assertSame(42, $card['age']);
        $this->assertSame('1779001', $card['patient_no']);
        $this->assertSame('***3358', $card['phone_masked']);
        $this->assertContains('VIP', $card['tags']);
        $this->assertSame('2026-09-01', $card['last_visit']['date']);
        $this->assertSame('关立亚', $card['last_visit']['doctor']);
        $this->assertSame('洗牙', $card['last_visit']['service']);
        $this->assertEquals(120.0, (float) $card['balance_due']);
        $this->assertEquals(50.0, (float) $card['member_balance']);
        $this->assertArrayNotHasKey('actions', $card);
        $this->assertArrayNotHasKey('phone_no', $card);
    }

    /** @test */
    public function card_search_falls_back_to_visit_type_when_no_service(): void
    {
        Appointment::where('patient_id', $this->patient->id)->delete();

        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->admin->id,
            'service_id' => null,
            'appointment_type' => 'revisit',
            'start_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'status' => Appointment::STATUS_CHECKED_IN,
            '_who_added' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/search-patient?' . http_build_query(['q' => '孙', 'card' => 1]));

        $response->assertOk();
        $card = collect($response->json())->firstWhere('id', $this->patient->id);
        $this->assertNotNull($card);
        $this->assertSame('复诊', $card['last_visit']['service']);
    }

    /** @test */
    public function card_search_derives_age_from_national_id_when_dob_missing(): void
    {
        $this->patient->update([
            'date_of_birth' => null,
            'age' => null,
            // 1984-03-15 → 约 42 岁（相对 2026-09）
            'nin' => '110101198403151234',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/search-patient?' . http_build_query(['q' => '孙', 'card' => 1]));

        $card = collect($response->json())->firstWhere('id', $this->patient->id);
        $this->assertSame(42, $card['age']);
    }

    /** @test */
    public function full_search_still_returns_patient_model_fields_for_select2(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/search-patient?' . http_build_query(['q' => '孙', 'full' => 1]));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $row = $data[0];
        $this->assertArrayHasKey('surname', $row);
        $this->assertArrayHasKey('othername', $row);
        $this->assertArrayHasKey('phone_no', $row);
        $this->assertArrayHasKey('gender', $row);
    }
}
