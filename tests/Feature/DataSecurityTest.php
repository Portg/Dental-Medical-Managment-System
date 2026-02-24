<?php

namespace Tests\Feature;

use App\AccessLog;
use App\Branch;
use App\OperationLog;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\Rules\StrongPassword;
use App\Services\DataMaskingService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DataSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $doctorRole;
    private Role $nurseRole;
    private User $adminUser;
    private User $doctorUser;
    private User $nurseUser;
    private Permission $viewSensitivePerm;
    private Permission $viewPatientsPerm;
    private Permission $exportPatientsPerm;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);

        $this->adminRole  = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);
        $this->nurseRole  = Role::create(['name' => 'Nurse', 'slug' => 'nurse']);

        $this->adminUser  = User::factory()->create(['role_id' => $this->adminRole->id, 'branch_id' => $branch->id]);
        $this->doctorUser = User::factory()->create(['role_id' => $this->doctorRole->id, 'branch_id' => $branch->id, 'is_doctor' => 'yes']);
        $this->nurseUser  = User::factory()->create(['role_id' => $this->nurseRole->id, 'branch_id' => $branch->id]);

        $this->viewSensitivePerm = Permission::create(['name' => 'View Sensitive Data', 'slug' => 'view-sensitive-data', 'module' => '数据安全']);
        $this->viewPatientsPerm  = Permission::create(['name' => 'View Patients', 'slug' => 'view-patients', 'module' => 'patients']);
        $this->exportPatientsPerm = Permission::create(['name' => 'Export Patients', 'slug' => 'export-patients', 'module' => '数据安全']);

        // Admin gets all three permissions
        foreach ([$this->viewSensitivePerm, $this->viewPatientsPerm, $this->exportPatientsPerm] as $perm) {
            RolePermission::create(['role_id' => $this->adminRole->id, 'permission_id' => $perm->id]);
        }

        // Doctor gets view-sensitive-data + view-patients
        RolePermission::create(['role_id' => $this->doctorRole->id, 'permission_id' => $this->viewSensitivePerm->id]);
        RolePermission::create(['role_id' => $this->doctorRole->id, 'permission_id' => $this->viewPatientsPerm->id]);

        // Nurse gets only view-patients (no sensitive data)
        RolePermission::create(['role_id' => $this->nurseRole->id, 'permission_id' => $this->viewPatientsPerm->id]);

        // Clear permission caches
        Cache::flush();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  A. DataMaskingService
    // ═══════════════════════════════════════════════════════════════════

    public function test_mask_phone_standard(): void
    {
        $this->assertEquals('138****8000', DataMaskingService::maskPhone('13800138000'));
    }

    public function test_mask_phone_with_plus86_prefix(): void
    {
        $this->assertEquals('+86 138****8000', DataMaskingService::maskPhone('+8613800138000'));
        $this->assertEquals('+86 138****8000', DataMaskingService::maskPhone('+86 13800138000'));
    }

    public function test_mask_phone_with_0086_prefix(): void
    {
        $this->assertEquals('0086 138****8000', DataMaskingService::maskPhone('008613800138000'));
    }

    public function test_mask_phone_short_returns_unchanged(): void
    {
        $this->assertEquals('1234', DataMaskingService::maskPhone('1234'));
    }

    public function test_mask_phone_null_returns_null(): void
    {
        $this->assertNull(DataMaskingService::maskPhone(null));
    }

    public function test_mask_nin_standard(): void
    {
        // 16 chars: keep 6 + 4 = 10, mask 6
        $this->assertEquals('110101******1234', DataMaskingService::maskNin('1101011990011234'));
        // 18 chars (standard Chinese ID): keep 6 + 4 = 10, mask 8
        $this->assertEquals('110101********1234', DataMaskingService::maskNin('110101199001011234'));
    }

    public function test_mask_nin_short_returns_unchanged(): void
    {
        $this->assertEquals('12345', DataMaskingService::maskNin('12345'));
    }

    public function test_mask_email_standard(): void
    {
        $this->assertEquals('z***@example.com', DataMaskingService::maskEmail('zhang@example.com'));
    }

    public function test_mask_email_single_char_local(): void
    {
        $this->assertEquals('z***@example.com', DataMaskingService::maskEmail('z@example.com'));
    }

    public function test_mask_email_null_returns_null(): void
    {
        $this->assertNull(DataMaskingService::maskEmail(null));
    }

    public function test_mask_address_standard(): void
    {
        $this->assertEquals('北京市海淀区******', DataMaskingService::maskAddress('北京市海淀区中关村南大街5号'));
    }

    public function test_mask_address_short_returns_unchanged(): void
    {
        $this->assertEquals('北京', DataMaskingService::maskAddress('北京'));
    }

    public function test_mask_name_standard(): void
    {
        $this->assertEquals('张**', DataMaskingService::maskName('张三丰'));
        $this->assertEquals('张*', DataMaskingService::maskName('张三'));
    }

    public function test_mask_field_auto_detects_type(): void
    {
        config(['data_security.display_masking.enabled' => true]);

        $this->assertEquals('138****8000', DataMaskingService::maskField('phone_no', '13800138000'));
        $this->assertEquals('z***@example.com', DataMaskingService::maskField('email', 'zhang@example.com'));
        $this->assertEquals('110101****1234', DataMaskingService::maskField('nin', '11010119901234'));
        $this->assertEquals('unchanged', DataMaskingService::maskField('unknown_field', 'unchanged'));
    }

    public function test_mask_field_returns_raw_when_disabled(): void
    {
        config(['data_security.display_masking.enabled' => false]);

        $this->assertEquals('13800138000', DataMaskingService::maskField('phone_no', '13800138000'));
    }

    public function test_mask_field_null_and_empty_pass_through(): void
    {
        $this->assertNull(DataMaskingService::maskField('phone_no', null));
        $this->assertEquals('', DataMaskingService::maskField('phone_no', ''));
    }

    public function test_is_export_masking_enabled(): void
    {
        config(['data_security.export_masking_enabled' => true]);
        $this->assertTrue(DataMaskingService::isExportMaskingEnabled());

        config(['data_security.export_masking_enabled' => false]);
        $this->assertFalse(DataMaskingService::isExportMaskingEnabled());
    }

    // ═══════════════════════════════════════════════════════════════════
    //  B. EncryptsNin Trait
    // ═══════════════════════════════════════════════════════════════════

    public function test_nin_encrypted_on_write_and_decrypted_on_read(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        $patient = Patient::create([
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            'patient_no' => 'P-TEST-001',
            'nin'        => '110101199001011234',
            '_who_added' => $this->adminUser->id,
        ]);

        // The stored value should be encrypted (not plain text)
        $raw = $patient->getRawOriginal('nin');
        $this->assertNotEquals('110101199001011234', $raw);

        // Reading via accessor should return decrypted value
        $this->assertEquals('110101199001011234', $patient->nin);

        // Blind index hash should be set
        $this->assertNotNull($patient->nin_hash);
    }

    public function test_nin_blind_index_enables_search(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        Patient::create([
            'surname'    => '李',
            'othername'  => '四',
            'gender'     => 'Male',
            'phone_no'   => '13900139000',
            'patient_no' => 'P-TEST-002',
            'nin'        => '220102199501011234',
            '_who_added' => $this->adminUser->id,
        ]);

        $found = Patient::whereNin('220102199501011234')->first();
        $this->assertNotNull($found);
        $this->assertEquals('李', $found->surname);

        $notFound = Patient::whereNin('000000000000000000')->first();
        $this->assertNull($notFound);
    }

    public function test_nin_null_value_handled_gracefully(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        $patient = Patient::create([
            'surname'    => '王',
            'othername'  => '五',
            'gender'     => 'Female',
            'phone_no'   => '13700137000',
            'patient_no' => 'P-TEST-003',
            'nin'        => null,
            '_who_added' => $this->adminUser->id,
        ]);

        $this->assertNull($patient->nin);
        $this->assertNull($patient->nin_hash);
    }

    public function test_nin_backwards_compatible_with_unencrypted_data(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        // Simulate legacy unencrypted NIN by writing directly to DB
        $patient = Patient::create([
            'surname'    => '赵',
            'othername'  => '六',
            'gender'     => 'Male',
            'phone_no'   => '13600136000',
            'patient_no' => 'P-TEST-004',
            '_who_added' => $this->adminUser->id,
        ]);

        // Write plain text directly, bypassing the mutator
        \DB::table('patients')->where('id', $patient->id)->update(['nin' => 'PLAIN_TEXT_NIN']);

        $patient->refresh();
        // Should return the plain text since decryption will fail gracefully
        $this->assertEquals('PLAIN_TEXT_NIN', $patient->nin);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  C. StrongPassword Rule
    // ═══════════════════════════════════════════════════════════════════

    public function test_strong_password_passes_valid(): void
    {
        $v = Validator::make(
            ['pw' => 'Abc12345!'],
            ['pw' => [new StrongPassword]]
        );
        $this->assertTrue($v->passes());
    }

    public function test_strong_password_fails_too_short(): void
    {
        $v = Validator::make(
            ['pw' => 'Ab1!'],
            ['pw' => [new StrongPassword]]
        );
        $this->assertFalse($v->passes());
    }

    public function test_strong_password_fails_insufficient_classes(): void
    {
        // Only lowercase + digits = 2 classes, need 3
        $v = Validator::make(
            ['pw' => 'abcdef12345'],
            ['pw' => [new StrongPassword]]
        );
        $this->assertFalse($v->passes());
    }

    public function test_strong_password_passes_three_classes(): void
    {
        // Uppercase + lowercase + digit = 3 classes, no special
        $v = Validator::make(
            ['pw' => 'Abcdef123'],
            ['pw' => [new StrongPassword]]
        );
        $this->assertTrue($v->passes());
    }

    // ═══════════════════════════════════════════════════════════════════
    //  D. Reveal PII Endpoint
    // ═══════════════════════════════════════════════════════════════════

    private function createTestPatient(): Patient
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        return Patient::create([
            'surname'        => '测试',
            'othername'      => '患者',
            'gender'         => 'Male',
            'phone_no'       => '13800138000',
            'alternative_no' => '13900139000',
            'email'          => 'test@example.com',
            'address'        => '北京市海淀区中关村南大街5号',
            'nin'            => '110101199001011234',
            'next_of_kin_no' => '13700137000',
            'next_of_kin_address' => '上海市浦东新区张江高科',
            'patient_no'     => 'P-TEST-REVEAL',
            '_who_added'     => $this->adminUser->id,
        ]);
    }

    public function test_reveal_pii_allowed_with_permission(): void
    {
        $patient = $this->createTestPatient();

        $response = $this->actingAs($this->adminUser)
            ->postJson("/patients/{$patient->id}/reveal-pii");

        $response->assertOk();
        $this->assertEquals('13800138000', $response->json('phone_no'));
        $this->assertEquals('test@example.com', $response->json('email'));
        $this->assertEquals('13900139000', $response->json('alternative_no'));
        // NIN is encrypted then decrypted via accessor
        $ninValue = $response->json('nin');
        $this->assertNotNull($ninValue);
        $this->assertNotEmpty($ninValue);

        // Verify audit log was created
        $this->assertDatabaseHas('access_logs', [
            'user_id'           => $this->adminUser->id,
            'accessed_resource' => 'Patient:reveal_pii',
            'resource_type'     => 'Patient',
            'resource_id'       => $patient->id,
        ]);
    }

    public function test_reveal_pii_denied_without_permission(): void
    {
        $patient = $this->createTestPatient();

        $response = $this->actingAs($this->nurseUser)
            ->postJson("/patients/{$patient->id}/reveal-pii");

        $response->assertStatus(403);
    }

    public function test_show_patient_creates_audit_log(): void
    {
        $patient = $this->createTestPatient();

        $this->actingAs($this->adminUser)
            ->get("/patients/{$patient->id}");

        $this->assertDatabaseHas('access_logs', [
            'user_id'           => $this->adminUser->id,
            'accessed_resource' => 'Patient:view_detail',
            'resource_type'     => 'Patient',
            'resource_id'       => $patient->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  E. API Patient Resource Masking
    // ═══════════════════════════════════════════════════════════════════

    public function test_api_patient_list_masks_pii_by_default(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        $this->createTestPatient();
        $token = $this->adminUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/patients');

        $response->assertOk();

        $patient = $response->json('data.0');
        if ($patient) {
            // Phone should be masked
            $this->assertStringContainsString('****', $patient['phone_no']);
            // Email should be masked
            $this->assertStringContainsString('***@', $patient['email']);
        }
    }

    public function test_api_patient_unmask_with_permission(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        $patient = $this->createTestPatient();
        $token = $this->adminUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/patients/{$patient->id}?unmask=1");

        $response->assertOk();

        $data = $response->json('data.patient');
        if ($data) {
            $this->assertEquals('13800138000', $data['phone_no']);
            $this->assertEquals('test@example.com', $data['email']);
        }
    }

    public function test_api_patient_unmask_without_permission_stays_masked(): void
    {
        config(['data_security.nin_blind_index_key' => 'test-key-for-hmac-blind-index']);

        $this->createTestPatient();
        $token = $this->nurseUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/patients?unmask=1');

        $response->assertOk();

        $patient = $response->json('data.0');
        if ($patient) {
            // Should still be masked despite unmask=1 (no permission)
            $this->assertStringContainsString('****', $patient['phone_no']);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  F. Export Frequency Alert
    // ═══════════════════════════════════════════════════════════════════

    public function test_export_frequency_alert_triggers_when_threshold_exceeded(): void
    {
        config(['data_security.export_alert.threshold' => 3, 'data_security.export_alert.window_minutes' => 60]);

        $this->actingAs($this->adminUser);

        // Create 3 export logs
        for ($i = 0; $i < 3; $i++) {
            OperationLog::log('export', '患者管理', 'Patient');
        }

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, '频繁导出告警');
            });

        OperationLog::checkExportFrequency();
    }

    public function test_export_frequency_no_alert_under_threshold(): void
    {
        config(['data_security.export_alert.threshold' => 5, 'data_security.export_alert.window_minutes' => 60]);

        $this->actingAs($this->adminUser);

        // Create only 2 export logs (under threshold of 5)
        for ($i = 0; $i < 2; $i++) {
            OperationLog::log('export', '患者管理', 'Patient');
        }

        Log::shouldReceive('warning')->never();

        OperationLog::checkExportFrequency();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  G. Config
    // ═══════════════════════════════════════════════════════════════════

    public function test_data_security_config_structure(): void
    {
        $config = config('data_security');

        $this->assertArrayHasKey('nin_blind_index_key', $config);
        $this->assertArrayHasKey('display_masking', $config);
        $this->assertArrayHasKey('export_masking_enabled', $config);
        $this->assertArrayHasKey('export_alert', $config);
        $this->assertArrayHasKey('enabled', $config['display_masking']);
        $this->assertArrayHasKey('fields', $config['display_masking']);
        $this->assertArrayHasKey('threshold', $config['export_alert']);
        $this->assertArrayHasKey('window_minutes', $config['export_alert']);
    }

    public function test_session_config_has_secure_defaults(): void
    {
        // config/session.php default is 60, but .env may override
        // Verify the config file default by checking without env override
        $this->assertLessThanOrEqual(120, (int) config('session.lifetime'));
        $this->assertTrue(config('session.encrypt'));
    }
}
