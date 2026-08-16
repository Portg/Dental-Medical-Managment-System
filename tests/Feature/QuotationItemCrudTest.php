<?php

namespace Tests\Feature;

use App\Branch;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\Quotation;
use App\QuotationItem;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 报价单详情页的「追加/编辑明细」这条路，此前从头到尾没跑通过。
 *
 *   - 追加必 500：控制器把 quotation_id 过滤掉了（$request->only 里没这个键），
 *     而 QuotationItemService::create() 必读它 —— PHP 8 下未定义数组键会被
 *     Laravel 的错误处理器抛成 ErrorException。
 *   - 编辑弹窗单价永远是空的：接口回的是原始行（单价存在 amount 列），
 *     前端读的是 data.price。
 *   - 明细表声明了 tooth_no 这一列，库里却没有这个字段，界面上三处又都在收 ——
 *     DataTables 拿不到声明过的列会整表弹「Requested unknown parameter」。
 */
class QuotationItemCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Quotation $quotation;
    private MedicalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Receptionist', 'slug' => 'receptionist']);

        $perm = Permission::create([
            'name'   => 'manage-quotations',
            'slug'   => 'manage-quotations',
            'module' => '报价单',
        ]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);

        $this->user = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        $patient = Patient::create([
            'patient_no' => '20260301',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            '_who_added' => $this->user->id,
        ]);

        $this->quotation = Quotation::create([
            'quotation_no' => 'Q-100',
            'patient_id'   => $patient->id,
            '_who_added'   => $this->user->id,
        ]);

        $this->service = MedicalService::create([
            'name'       => '根管治疗',
            'price'      => 800,
            '_who_added' => $this->user->id,
        ]);
    }

    /** @test */
    public function 追加明细会落库而不是500(): void
    {
        $response = $this->actingAs($this->user)->postJson('/quotation-items', [
            'quotation_id'       => $this->quotation->id,
            'medical_service_id' => $this->service->id,
            'qty'                => 2,
            'price'              => 800,
            'tooth_no'           => '36',
        ]);

        $response->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id'       => $this->quotation->id,
            'medical_service_id' => $this->service->id,
            'qty'                => 2,
            // 列名叫 amount，存的是单价
            'amount'             => 800,
            'tooth_no'           => '36',
            '_who_added'         => $this->user->id,
        ]);
    }

    /** @test */
    public function 追加明细必须带报价单号(): void
    {
        $this->actingAs($this->user)->postJson('/quotation-items', [
            'medical_service_id' => $this->service->id,
            'qty'                => 1,
            'price'              => 800,
        ])->assertStatus(422);

        $this->assertSame(0, QuotationItem::count());
    }

    /** @test */
    public function 追加明细拒绝负单价与非数字(): void
    {
        foreach ([-1, 'abc'] as $price) {
            $this->actingAs($this->user)->postJson('/quotation-items', [
                'quotation_id'       => $this->quotation->id,
                'medical_service_id' => $this->service->id,
                'qty'                => 1,
                'price'              => $price,
            ])->assertStatus(422);
        }

        $this->assertSame(0, QuotationItem::count());
    }

    /**
     * 编辑弹窗靠这个接口回填单价。字段名对不上就是一个空的单价输入框，
     * 小计还会算成 NaN —— 页面不报错，但保存下去金额就没了。
     */
    /** @test */
    public function 编辑接口回传的单价字段前端读得到(): void
    {
        $item = QuotationItem::create([
            'qty'                => 3,
            'amount'             => 250,
            'tooth_no'           => '11,12',
            'quotation_id'       => $this->quotation->id,
            'medical_service_id' => $this->service->id,
            '_who_added'         => $this->user->id,
        ]);

        $payload = $this->actingAs($this->user)
            ->getJson('/quotation-items/' . $item->id . '/edit')
            ->assertStatus(200)
            ->json();

        // quotations_show_index.js 读的正是这三个键
        $this->assertEquals(250, $payload['amount']);
        $this->assertEquals(3, $payload['qty']);
        $this->assertSame('11,12', $payload['tooth_no']);
        $this->assertEquals($this->service->id, $payload['medical_service_id']);
    }

    /** @test */
    public function 更新明细会改掉单价数量与牙位(): void
    {
        $item = QuotationItem::create([
            'qty'                => 1,
            'amount'             => 100,
            'quotation_id'       => $this->quotation->id,
            'medical_service_id' => $this->service->id,
            '_who_added'         => $this->user->id,
        ]);

        $this->actingAs($this->user)->putJson('/quotation-items/' . $item->id, [
            'medical_service_id' => $this->service->id,
            'qty'                => 4,
            'price'              => 320,
            'tooth_no'           => '46',
        ])->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('quotation_items', [
            'id'       => $item->id,
            'qty'      => 4,
            'amount'   => 320,
            'tooth_no' => '46',
        ]);
    }

    /**
     * 明细表的每一列都得有值，尤其是 tooth_no —— 前端声明了这一列，
     * 服务端不给就是整表「Requested unknown parameter」。
     */
    /** @test */
    public function 明细表返回前端声明的每一列(): void
    {
        QuotationItem::create([
            'qty'                => 2,
            'amount'             => 150,
            'tooth_no'           => '21',
            'quotation_id'       => $this->quotation->id,
            'medical_service_id' => $this->service->id,
            '_who_added'         => $this->user->id,
        ]);

        $row = $this->actingAs($this->user)
            ->get('/quotation-items/' . $this->quotation->id . '?draw=1&start=0&length=10', [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertStatus(200)
            ->json('data.0');

        foreach (['service', 'tooth_no', 'qty', 'price', 'total_amount', 'added_by'] as $column) {
            $this->assertArrayHasKey($column, $row, "明细表缺列 {$column}，DataTables 会整表报错");
        }

        $this->assertSame('21', $row['tooth_no']);
        $this->assertSame('300', $row['total_amount']);
    }

    /**
     * 新建报价单页的 addmore[N][tooth_no] 也一直在提交，此前同样被丢掉，
     * 打印模板里的牙位于是永远是空的。
     */
    /** @test */
    public function 新建报价单时明细的牙位会落库(): void
    {
        $patientId = $this->quotation->patient_id;

        $this->actingAs($this->user)->postJson('/quotations', [
            'patient_id' => $patientId,
            'addmore'    => [
                ['medical_service_id' => $this->service->id, 'qty' => 1, 'price' => 800, 'tooth_no' => '16'],
            ],
        ])->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('quotation_items', [
            'medical_service_id' => $this->service->id,
            'amount'             => 800,
            'tooth_no'           => '16',
        ]);
    }
}
