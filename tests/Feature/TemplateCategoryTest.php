<?php

namespace Tests\Feature;

use App\Branch;
use App\MedicalTemplate;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\Services\MedicalTemplateService;
use App\Services\TemplateCategoryService;
use App\TemplateCategory;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 病历模板的学科分类树（最多三级）。
 *
 * 对齐视频：模板按学科分（口腔正畸学 / 牙体牙髓病学 / …）并支持三级类型。
 *
 * 最要紧的一条是**不要和既有的 category 混**：medical_templates.category 是
 * 模板的归属范围（system / department / personal，决定谁能看见谁能改），
 * 本表是学科分类。一列担两个语义的话，权限判断和分类筛选会互相踩。
 *
 * 其余盯的是树本身的完整性：三级封顶、不能挂到自己的子树下、下面还有东西时
 * 不让删（级联删会把医生一条条写出来的模板带走）。
 */
class TemplateCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        foreach (['manage-medical-services', 'edit-patients'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '设置']
            )->id;
        }

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin-tplcat']);
        foreach ($perms as $permId) {
            RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $permId]);
        }

        // 只能看树、不能改树：改树是设置类操作
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor-tplcat']);
        RolePermission::create(['role_id' => $doctorRole->id, 'permission_id' => $perms['edit-patients']]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $doctorRole->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function service(): TemplateCategoryService
    {
        return app(TemplateCategoryService::class);
    }

    private function makeCategory(string $name, ?int $parentId = null): TemplateCategory
    {
        $result = $this->service()->create(
            ['name' => $name, 'parent_id' => $parentId],
            $this->admin->id
        );
        $this->assertTrue($result['success'], $result['message'] ?? '');

        return TemplateCategory::findOrFail($result['id']);
    }

    private function makeTemplate(string $name, ?int $categoryId): MedicalTemplate
    {
        return app(MedicalTemplateService::class)->createTemplate([
            'name'                 => $name,
            'category'             => 'system',
            'template_category_id' => $categoryId,
            'type'                 => 'diagnosis',
            'content'              => '内容',
            'code'                 => 'T-' . uniqid(),
        ], $this->admin->id);
    }

    /** @test */
    public function 能建出三级树并带上子树计数(): void
    {
        $root  = $this->makeCategory('牙体牙髓病学');
        $mid   = $this->makeCategory('牙髓病', $root->id);
        $leaf  = $this->makeCategory('急性牙髓炎', $mid->id);

        $this->makeTemplate('急性牙髓炎初诊', $leaf->id);
        $this->makeTemplate('牙髓病复诊', $mid->id);

        $tree = $this->service()->tree();

        $this->assertCount(1, $tree);
        $this->assertSame('牙体牙髓病学', $tree[0]['name']);
        // 自己名下 0 条，子树合计 2 条 —— 父节点折叠时也要看得出下面有东西
        $this->assertSame(0, $tree[0]['template_count']);
        $this->assertSame(2, $tree[0]['total_count']);
        $this->assertSame(1, $tree[0]['children'][0]['template_count']);
        $this->assertSame('急性牙髓炎', $tree[0]['children'][0]['children'][0]['name']);
    }

    /** @test */
    public function 第四级会被拒(): void
    {
        $l1 = $this->makeCategory('口腔修复学');
        $l2 = $this->makeCategory('固定修复', $l1->id);
        $l3 = $this->makeCategory('全瓷冠', $l2->id);

        $result = $this->service()->create(['name' => '第四级', 'parent_id' => $l3->id], $this->admin->id);

        $this->assertFalse($result['success']);
        $this->assertSame(3, TemplateCategory::count());
    }

    /**
     * 挂到自己的子树下会把子树从树上切下来，变成一个谁也看不见的环。
     */
    /** @test */
    public function 不能把分类挂到自己的下级里(): void
    {
        $root  = $this->makeCategory('口腔正畸学');
        $child = $this->makeCategory('固定矫治', $root->id);

        $result = $this->service()->update($root->id, ['parent_id' => $child->id], $this->admin->id);

        $this->assertFalse($result['success']);
        $root->refresh();
        $this->assertNull($root->parent_id);
    }

    /** @test */
    public function 不能把自己挂到自己下面(): void
    {
        $root = $this->makeCategory('牙周病学');

        $result = $this->service()->update($root->id, ['parent_id' => $root->id], $this->admin->id);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function 移动后超过三级会被拒(): void
    {
        // 待移动的子树本身有两层
        $srcRoot = $this->makeCategory('源');
        $this->makeCategory('源子', $srcRoot->id);

        // 目标已经在第二层，接上去就是四层
        $dstRoot = $this->makeCategory('目标');
        $dstMid  = $this->makeCategory('目标子', $dstRoot->id);

        $result = $this->service()->update($srcRoot->id, ['parent_id' => $dstMid->id], $this->admin->id);

        $this->assertFalse($result['success']);
        $srcRoot->refresh();
        $this->assertNull($srcRoot->parent_id);
    }

    /** @test */
    public function 下面还有子分类时不让删(): void
    {
        $root = $this->makeCategory('颌面外科学');
        $this->makeCategory('拔牙', $root->id);

        $result = $this->service()->delete($root->id);

        $this->assertFalse($result['success']);
        $this->assertSame(2, TemplateCategory::count());
    }

    /** @test */
    public function 下面还有模板时不让删(): void
    {
        $cat = $this->makeCategory('口腔种植学');
        $this->makeTemplate('种植一期', $cat->id);

        $result = $this->service()->delete($cat->id);

        $this->assertFalse($result['success']);
        $this->assertSame(1, TemplateCategory::count());
    }

    /** @test */
    public function 空分类可以删(): void
    {
        $cat = $this->makeCategory('颞颌关节病');

        $this->assertTrue($this->service()->delete($cat->id)['success']);
        $this->assertSame(0, TemplateCategory::count());
    }

    /**
     * 点一级分类要能看到整棵子树下的模板，否则父节点永远是空的，
     * 前台会以为模板没归好类。
     */
    /** @test */
    public function 按一级分类筛能筛出子树里的模板(): void
    {
        $root = $this->makeCategory('牙体牙髓病学');
        $mid  = $this->makeCategory('牙髓病', $root->id);

        $this->makeTemplate('牙髓病模板', $mid->id);
        $this->makeTemplate('别的科模板', $this->makeCategory('牙周病学')->id);

        $rows = app(MedicalTemplateService::class)
            ->getTemplateList(['template_category_id' => $root->id]);

        $this->assertCount(1, $rows);
        $this->assertSame('牙髓病模板', $rows->first()->name);
    }

    /** @test */
    public function 能筛出未归类的模板(): void
    {
        $this->makeTemplate('没归类的', null);
        $this->makeTemplate('归了类的', $this->makeCategory('牙周病学')->id);

        $rows = app(MedicalTemplateService::class)
            ->getTemplateList(['template_category_id' => 'none']);

        $this->assertCount(1, $rows);
        $this->assertSame('没归类的', $rows->first()->name);
    }

    /**
     * 学科分类不能和 medical_templates.category（归属范围）混：
     * 两者各管各的，互不影响。
     */
    /** @test */
    public function 学科分类与归属范围互不影响(): void
    {
        $cat = $this->makeCategory('口腔修复学');
        $template = $this->makeTemplate('全瓷冠模板', $cat->id);

        $this->assertSame('system', $template->category);
        $this->assertSame($cat->id, $template->template_category_id);

        // 按归属范围筛仍然只认 category
        $rows = app(MedicalTemplateService::class)->getTemplateList(['category' => 'personal']);
        $this->assertCount(0, $rows);
    }

    /** @test */
    public function 只能看的人改不了树(): void
    {
        $cat = $this->makeCategory('牙周病学');

        $this->actingAs($this->doctor)
            ->getJson('/template-categories')
            ->assertOk();

        $this->actingAs($this->doctor)
            ->postJson('/template-categories', ['name' => '新分类'])
            ->assertForbidden();

        $this->actingAs($this->doctor)
            ->deleteJson('/template-categories/' . $cat->id)
            ->assertForbidden();
    }

    /**
     * 模板管理页的准入是 manage-medical-services，而分类树读取一度只配了
     * edit-patients —— 只有前者的模板管理员进得了页面、拿不到树（403），
     * 前端又把弹窗的 show 放在成功回调里，表现就是「点了没反应」。
     * 这条用例把两边的准入钉在一起。
     */
    /** @test */
    public function 模板管理员进得了页面就读得到分类树(): void
    {
        $role = Role::create(['name' => 'TplAdmin', 'slug' => 'tpl-admin-only']);
        RolePermission::create([
            'role_id'       => $role->id,
            'permission_id' => Permission::where('slug', 'manage-medical-services')->value('id'),
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $this->admin->branch_id, 'status' => User::STATUS_ACTIVE,
        ]);

        // 进得了模板管理页
        $this->actingAs($user)->get('/medical-templates')->assertOk();
        // 就必须读得到这棵树
        $this->actingAs($user)->getJson('/template-categories')->assertOk();
    }

    /** @test */
    public function 模板页把分类树的入口与资源都渲染出来(): void
    {
        $html = $this->actingAs($this->admin)->get('/medical-templates')->assertOk()->getContent();

        foreach (['openCategoryManager', 'category-manager-modal', 'tc-tree',
                  'filter_template_category', 'template_categories.js'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    /** @test */
    public function 接口拒绝超三级并返回422(): void
    {
        $l1 = $this->makeCategory('一');
        $l2 = $this->makeCategory('二', $l1->id);
        $l3 = $this->makeCategory('三', $l2->id);

        $this->actingAs($this->admin)
            ->postJson('/template-categories', ['name' => '四', 'parent_id' => $l3->id])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    /** @test */
    public function 种子建出视频里的七个学科(): void
    {
        $this->seed(\Database\Seeders\TemplateCategoriesSeeder::class);

        $roots = TemplateCategory::whereNull('parent_id')->pluck('name')->all();

        foreach (['口腔正畸学', '颌面外科学', '牙体牙髓病学', '牙周病学',
                  '颞颌关节病', '口腔种植学', '口腔修复学'] as $name) {
            $this->assertContains($name, $roots);
        }

        // 幂等：重复跑不会长出第二棵树
        $before = TemplateCategory::count();
        $this->seed(\Database\Seeders\TemplateCategoriesSeeder::class);
        $this->assertSame($before, TemplateCategory::count());
    }
}
