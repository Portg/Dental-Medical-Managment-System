<?php

namespace Tests\Feature;

use App\Role;
use App\Services\SterilizationService;
use App\SterilizationKit;
use App\SterilizationRecord;
use App\SterilizationUsage;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SterilizationUsageTest extends TestCase
{
    use RefreshDatabase;

    private SterilizationService $svc;
    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc    = app(SterilizationService::class);
        $this->roleId = Role::create(['name' => 'Admin', 'slug' => 'admin'])->id;
    }

    private function makeRecord(): SterilizationRecord
    {
        $kit = SterilizationKit::create(['kit_no' => 'K1', 'name' => '测试包', 'is_active' => true]);
        $op  = User::factory()->create(['role_id' => $this->roleId]);
        return $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
    }

    public function test_record_usage_sets_status_to_used(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create(['role_id' => $this->roleId]);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $record->refresh();
        $this->assertEquals(SterilizationRecord::STATUS_USED, $record->status);
    }

    public function test_record_usage_fills_snapshot_fields(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create(['surname' => '张医生', 'role_id' => $this->roleId]);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $usage = SterilizationUsage::where('record_id', $record->id)->first();
        $this->assertEquals($record->batch_no, $usage->batch_no);
        $this->assertNotNull($usage->kit_name);
        $this->assertNotNull($usage->doctor_name);
    }

    public function test_soft_delete_usage_rolls_back_status(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create(['role_id' => $this->roleId]);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $usage = SterilizationUsage::where('record_id', $record->id)->first();
        $this->svc->revokeUsage($usage->id);

        $record->refresh();
        $this->assertEquals(SterilizationRecord::STATUS_VALID, $record->status);
        $this->assertSoftDeleted($usage);
    }

    public function test_cannot_use_already_used_record(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create(['role_id' => $this->roleId]);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $this->expectException(\RuntimeException::class);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);
    }

    public function test_cannot_use_expired_record(): void
    {
        $kit = SterilizationKit::create(['kit_no' => 'K2', 'name' => '过期包', 'is_active' => true]);
        $op  = User::factory()->create(['role_id' => $this->roleId]);
        // 直接创建一条已过期的记录（expires_at 设为过去）
        $record = SterilizationRecord::create([
            'kit_id'        => $kit->id,
            'batch_no'      => 'S20200101-001',
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now()->subDays(100),
            'expires_at'    => now()->subDays(10),
            'status'        => SterilizationRecord::STATUS_VALID,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->svc->recordUsage($record->id, ['used_by' => $op->id, 'used_at' => now()]);
    }
}
