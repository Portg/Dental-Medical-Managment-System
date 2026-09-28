<?php

namespace Tests\Feature;

use App\Branch;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 患者详情页在患者**真有**过敏史/系统病时也要能打开。
 *
 * 此前不能：show.blade.php 对 drug_allergies / systemic_diseases 又做了一次
 * json_decode，而模型里这两列已经 cast 成 array —— 于是
 * 「json_decode(): Argument #1 must be of type string, array given」，整页 500。
 *
 * 之所以一直没暴露，是因为开发库里没有一个患者填过这两个字段；一旦真有，
 * 这页就打不开。这条用例把「有值」的情况钉住。
 */
class PatientShowAllergyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 患者有过敏史时详情页仍能打开(): void
    {
        $branch = Branch::first() ?: Branch::create(['name' => 'Main', 'is_active' => true]);
        $perm = Permission::firstOrCreate(['slug' => 'view-patients'],
            ['name' => 'view-patients', 'module' => '患者管理']);
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer-allergy']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);

        $user = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);

        $patient = Patient::create([
            'patient_no'        => 'ALG-' . uniqid(),
            'surname'           => '肖',
            'othername'         => '金华',
            'phone_no'          => '13313018690',
            'drug_allergies'    => ['penicillin'],
            'systemic_diseases' => ['hypertension'],
            '_who_added'        => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/patients/' . $patient->id)
            ->assertOk();
    }
}
