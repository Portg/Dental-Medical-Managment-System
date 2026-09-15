<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\QuickPhrase;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 「快捷短语」管理页要能维护语义槽位。
 *
 * 短语库按语义槽位分组之后（现病史是「时间 → 部位 → 症状 → …」），管理页必须跟上，
 * 否则我搭的 439 条初版就是一个只读的死库：医生加的新短语归不进槽位，进不了锚定
 * 面板；改一条已有短语还会把它的槽位清空，那条短语就从原来的组里掉出去。
 *
 * 原来管理页的问题有三个：
 *   shortcut 必填 —— 而系统自带的 439 条 shortcut 全是空的，医生一改就被拦下
 *   category 写死四个旧值 —— 新增的六个病历字段选不到
 *   完全没有 slot / sort_order 字段
 */
class QuickPhraseAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private User $otherDoctor;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        // 写入口按 scope 分权（见 QuickPhraseController::authorizeWrite）：
        //   system   → manage-settings（管理员）
        //   personal → edit-patients（医生，且只能动自己的）
        $perms = [];
        foreach (['manage-settings', 'edit-patients'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug], ['name' => $slug, 'module' => '系统设置']
            )->id;
        }

        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perms['manage-settings']]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perms['edit-patients']]);

        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);
        RolePermission::create(['role_id' => $doctorRole->id, 'permission_id' => $perms['edit-patients']]);

        $this->admin = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $doctorRole->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);
        $this->otherDoctor = User::factory()->create([
            'role_id' => $doctorRole->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'shortcut'   => '',
            'phrase'     => '牙龈退缩，',
            'category'   => 'examination',
            'slot'       => '牙周',
            'sort_order' => 3,
            'scope'      => 'system',
            'is_active'  => 1,
        ], $overrides);
    }

    public function test_新增短语能带上槽位与排序(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/quick-phrases', $this->payload())
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('quick_phrases', [
            'phrase'     => '牙龈退缩，',
            'category'   => 'examination',
            'slot'       => '牙周',
            'sort_order' => 3,
        ]);
    }

    /**
     * 简写选填。临床短语多为中文、量又大（现病史一栏 56 条），逐条编简写既没人
     * 记得住也容易撞；系统自带的 439 条 shortcut 全是空的，必填等于禁止医生维护。
     */
    public function test_简写可以不填(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/quick-phrases', $this->payload(['shortcut' => '']))
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('quick_phrases', ['phrase' => '牙龈退缩，']);
    }

    /**
     * category 必须落在病历的九个字段里 —— 锚定面板按它取短语，
     * 值对不上就进不了面板，医生会以为短语丢了。
     */
    public function test_不认识的段落被拒(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/quick-phrases', $this->payload(['category' => 'nonsense']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    public function test_新增的六个病历字段都能选(): void
    {
        foreach (['chief_complaint', 'present_illness', 'past_history',
                  'auxiliary_examination', 'treatment_plan', 'medical_orders'] as $field) {
            $this->actingAs($this->admin)
                ->postJson('/quick-phrases', $this->payload([
                    'phrase' => '测试短语' . $field, 'category' => $field, 'slot' => '测试组',
                ]))
                ->assertOk();
        }

        $this->assertSame(6, QuickPhrase::where('slot', '测试组')->count());
    }

    /**
     * 改一条已有短语不该把它的槽位弄丢 —— 丢了它就从原来那一组掉进「其他」。
     */
    public function test_编辑不会把槽位弄丢(): void
    {
        $phrase = QuickPhrase::create([
            'shortcut' => '', 'phrase' => '牙石(+)，', 'category' => 'examination',
            'slot' => '牙周', 'sort_order' => 2, 'scope' => 'system',
            'is_active' => true, '_who_added' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson('/quick-phrases/' . $phrase->id, $this->payload([
                'phrase' => '牙石(++)，', 'slot' => '牙周', 'sort_order' => 2,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('quick_phrases', [
            'id' => $phrase->id, 'phrase' => '牙石(++)，', 'slot' => '牙周', 'sort_order' => 2,
        ]);
    }

    /**
     * 医生加的短语要真的出现在锚定面板里 —— 这条链走通了，库才是活的。
     */
    public function test_医生加的短语会出现在锚定面板(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/quick-phrases', $this->payload([
                'phrase' => '本院自定义检查，', 'category' => 'examination', 'slot' => '自定义组',
            ]))->assertOk();

        $panel = QuickPhrase::panelForUser($this->admin->id);

        $this->assertArrayHasKey('自定义组', $panel['examination']);
        $this->assertContains('本院自定义检查，', $panel['examination']['自定义组']);
    }

    /**
     * 槽位下拉列的是库里已有的名字，省得手打错字开出一个只有一条的新组。
     */
    public function test_槽位下拉列出已有槽位(): void
    {
        QuickPhrase::create([
            'shortcut' => '', 'phrase' => 'A', 'category' => 'examination', 'slot' => '牙周',
            'scope' => 'system', 'is_active' => true, '_who_added' => $this->admin->id,
        ]);
        QuickPhrase::create([
            'shortcut' => '', 'phrase' => 'B', 'category' => 'examination', 'slot' => '龋坏',
            'scope' => 'system', 'is_active' => true, '_who_added' => $this->admin->id,
        ]);
        QuickPhrase::create([
            'shortcut' => '', 'phrase' => 'C', 'category' => 'examination', 'slot' => null,
            'scope' => 'system', 'is_active' => true, '_who_added' => $this->admin->id,
        ]);

        $slots = QuickPhrase::distinctSlots();

        $this->assertContains('牙周', $slots);
        $this->assertContains('龋坏', $slots);
        $this->assertNotContains(null, $slots, '空槽位不该出现在下拉里');
        $this->assertNotContains('', $slots);
    }

    // ─── 按 scope 分权 ──────────────────────────────────────────

    /**
     * 医生能管自己那套私人短语 —— 临床短语是医生的工具，
     * 原来整个写入口挂 manage-settings，等于医生一条都改不了。
     */
    public function test_医生能新增自己的私人短语(): void
    {
        $this->actingAs($this->doctor)
            ->postJson('/quick-phrases', $this->payload([
                'phrase' => '我自己的短语，', 'scope' => 'personal',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('quick_phrases', [
            'phrase' => '我自己的短语，', 'scope' => 'personal', 'user_id' => $this->doctor->id,
        ]);
    }

    /**
     * 全院共用的 system 短语，医生动不了 —— 改一条所有医生都受影响。
     */
    public function test_医生不能新增全院共用短语(): void
    {
        $this->actingAs($this->doctor)
            ->postJson('/quick-phrases', $this->payload(['scope' => 'system']))
            ->assertStatus(403);
    }

    public function test_医生不能改全院共用短语(): void
    {
        $sys = QuickPhrase::create([
            'shortcut' => '', 'phrase' => '全院的', 'category' => 'examination', 'slot' => '牙周',
            'scope' => 'system', 'is_active' => true, '_who_added' => $this->admin->id,
        ]);

        $this->actingAs($this->doctor)
            ->putJson('/quick-phrases/' . $sys->id, $this->payload(['phrase' => '被改了', 'scope' => 'system']))
            ->assertStatus(403);

        $this->assertDatabaseHas('quick_phrases', ['id' => $sys->id, 'phrase' => '全院的']);
    }

    /**
     * 不能把 system 短语「改成」personal 来绕开 manage-settings ——
     * authorizeWrite 同时检查目标 scope 与已有记录的 scope。
     */
    public function test_医生不能把全院短语降级成私人绕开权限(): void
    {
        $sys = QuickPhrase::create([
            'shortcut' => '', 'phrase' => '全院的', 'category' => 'examination', 'slot' => '牙周',
            'scope' => 'system', 'is_active' => true, '_who_added' => $this->admin->id,
        ]);

        $this->actingAs($this->doctor)
            ->putJson('/quick-phrases/' . $sys->id, $this->payload(['scope' => 'personal']))
            ->assertStatus(403);
    }

    /**
     * 私人短语只能动自己的 —— 否则医生可以改同事的那套。
     */
    public function test_医生不能改别人的私人短语(): void
    {
        $others = QuickPhrase::create([
            'shortcut' => '', 'phrase' => '同事的', 'category' => 'examination', 'slot' => '牙周',
            'scope' => 'personal', 'user_id' => $this->otherDoctor->id,
            'is_active' => true, '_who_added' => $this->otherDoctor->id,
        ]);

        $this->actingAs($this->doctor)
            ->putJson('/quick-phrases/' . $others->id, $this->payload(['phrase' => '被改了', 'scope' => 'personal']))
            ->assertStatus(403);

        $this->actingAs($this->doctor)
            ->deleteJson('/quick-phrases/' . $others->id)
            ->assertStatus(403);
    }

    public function test_医生能删自己的私人短语(): void
    {
        $mine = QuickPhrase::create([
            'shortcut' => '', 'phrase' => '我的', 'category' => 'examination', 'slot' => '牙周',
            'scope' => 'personal', 'user_id' => $this->doctor->id,
            'is_active' => true, '_who_added' => $this->doctor->id,
        ]);

        $this->actingAs($this->doctor)
            ->deleteJson('/quick-phrases/' . $mine->id)
            ->assertOk();

        $this->assertSoftDeleted('quick_phrases', ['id' => $mine->id]);
    }

    /** 管理员两种都能管 */
    public function test_管理员能管全院共用短语(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/quick-phrases', $this->payload(['scope' => 'system']))
            ->assertOk();
    }
}
