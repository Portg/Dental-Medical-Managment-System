<?php

namespace Tests\Feature;

use App\Role;
use App\Services\SterilizationService;
use App\SterilizationKit;
use App\SterilizationRecord;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SterilizationBatchNoTest extends TestCase
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

    private function makeKit(): SterilizationKit
    {
        return SterilizationKit::create([
            'kit_no' => 'KIT-001', 'name' => '洁牙包', 'is_active' => true,
        ]);
    }

    private function makeOperator(): User
    {
        return User::factory()->create(['role_id' => $this->roleId]);
    }

    public function test_generates_batch_no_with_correct_format(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();
        $record = $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
        $this->assertMatchesRegularExpression('/^S\d{8}-\d{3}$/', $record->batch_no);
    }

    public function test_batch_no_increments_within_same_day(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();

        $r1 = $this->svc->createRecord(['kit_id' => $kit->id, 'method' => SterilizationRecord::METHOD_AUTOCLAVE, 'operator_id' => $op->id, 'sterilized_at' => now()]);
        $r2 = $this->svc->createRecord(['kit_id' => $kit->id, 'method' => SterilizationRecord::METHOD_AUTOCLAVE, 'operator_id' => $op->id, 'sterilized_at' => now()]);

        $seq1 = (int) substr($r1->batch_no, -3);
        $seq2 = (int) substr($r2->batch_no, -3);
        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_autoclave_expires_in_90_days(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();
        $record = $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
        $diffDays = round(now()->diffInDays($record->expires_at));
        $this->assertEqualsWithDelta(90, $diffDays, 1);
    }

    public function test_chemical_expires_in_30_days(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();
        $record = $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_CHEMICAL,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
        $diffDays = round(now()->diffInDays($record->expires_at));
        $this->assertEqualsWithDelta(30, $diffDays, 1);
    }
}
