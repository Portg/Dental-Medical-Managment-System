<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\InventoryBatch;
use App\InventoryCategory;
use App\InventoryItem;
use App\OnlineBooking;
use App\Patient;
use App\Permission;
use App\Role;
use App\StockIn;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 工作台新增的待办队列。
 *
 * 参考视频里工作台左侧是 14 条队列，价值不在每一条多强，而在于**前台一天
 * 不用离开这一屏**。这几条的数据本来就有独立页面，这里只是把它们收进侧栏。
 *
 * 两类队列的行为刻意不同，这组用例把区别钉死：
 *   - 「预约已取消」跟当天走，翻到别的日期就该换一批
 *   - 「网络预约 / 资料待补 / 库存预警 / 有效期预警」是待办箱，不随日期变
 *     —— 昨天没处理的事，今天仍然要在台面上
 */
class TodayWorkQueuesTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        foreach (['view-appointments', 'manage-inventory'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '工作台']
            )->id;
        }

        $role = Role::create(['name' => 'Front Desk', 'slug' => 'front-desk-queues']);
        foreach ($perms as $permId) {
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $permId]);
        }

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'is_doctor' => true, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'Q-' . uniqid(),
            'surname'    => '张',
            'othername'  => '志伟',
            'phone_no'   => '15210743226',
            '_who_added' => $this->staff->id,
        ]);
    }

    /**
     * 预约号自己发：Appointment::AppointmentNo() 同一秒内连发会撞唯一键
     * （它按 latest() 取上一条算号，同秒内排序不定）—— 那是既有实现的问题，
     * 与本组用例无关，这里不让它干扰。
     */
    private int $aptSeq = 0;

    private function makeAppointment(string $date, string $status): Appointment
    {
        return Appointment::create([
            'appointment_no' => 900000 + (++$this->aptSeq),
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctor->id,
            'start_date'     => $date,
            'end_date'       => $date,
            'start_time'     => '09:30:00',
            'branch_id'      => $this->staff->branch_id,
            'status'         => $status,
            '_who_added'     => $this->staff->id,
        ]);
    }

    /** @test */
    public function 已取消队列只收当天被取消的预约(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $this->makeAppointment($today, Appointment::STATUS_CANCELLED);
        $this->makeAppointment($today, Appointment::STATUS_SCHEDULED);   // 没取消，不该进来
        $this->makeAppointment($yesterday, Appointment::STATUS_CANCELLED); // 不是今天

        $rows = $this->actingAs($this->staff)
            ->getJson('/today-work/cancelled')
            ->assertOk()
            ->json();

        $this->assertCount(1, $rows);
        $this->assertSame('张志伟', $rows[0]['patient_name']);
        $this->assertSame('09:30', $rows[0]['start_time']);

        // 翻到昨天该换一批，而不是固定显示今天
        $yesterdayRows = $this->actingAs($this->staff)
            ->getJson('/today-work/cancelled?date=' . $yesterday)
            ->assertOk()
            ->json();
        $this->assertCount(1, $yesterdayRows);
    }

    /** @test */
    public function 被拒绝的预约也算已取消(): void
    {
        $this->makeAppointment(date('Y-m-d'), Appointment::STATUS_REJECTED);

        $rows = $this->actingAs($this->staff)
            ->getJson('/today-work/cancelled')
            ->assertOk()
            ->json();

        $this->assertCount(1, $rows);
    }

    /**
     * 待办箱不随日期变：昨天提交的网络预约今天没处理，它仍然要在台面上。
     * 按日期筛会让它随着翻页消失，而前台以为已经处理完了。
     */
    /** @test */
    public function 网络预约队列只收待处理的且不随日期消失(): void
    {
        OnlineBooking::create([
            'full_name'  => '高月芬',
            'phone_no'   => '15148368510',
            'start_date' => date('Y-m-d', strtotime('-3 days')),
            'start_time' => '10:00:00',
            'status'     => OnlineBooking::STATUS_WAITING,
        ]);
        OnlineBooking::create([
            'full_name'  => '已处理的',
            'phone_no'   => '15148368511',
            'start_date' => date('Y-m-d'),
            'status'     => OnlineBooking::STATUS_ACCEPTED,
        ]);

        $rows = $this->actingAs($this->staff)
            ->getJson('/today-work/online-bookings?date=' . date('Y-m-d'))
            ->assertOk()
            ->json();

        $this->assertCount(1, $rows);
        $this->assertSame('高月芬', $rows[0]['full_name']);
        $this->assertSame('151****8510', $rows[0]['phone']);
    }

    /** @test */
    public function 库存预警只收低于预警线的在用物料(): void
    {
        $category = InventoryCategory::create(['name' => '耗材', 'is_active' => true, '_who_added' => $this->staff->id]);

        $low = InventoryItem::create([
            'name' => '手套', 'item_code' => 'GL-01', 'category_id' => $category->id,
            'unit' => '盒', 'current_stock' => 2, 'stock_warning_level' => 10, 'is_active' => true, '_who_added' => $this->staff->id,
        ]);
        InventoryItem::create([
            'name' => '口罩', 'item_code' => 'MK-01', 'category_id' => $category->id,
            'unit' => '盒', 'current_stock' => 50, 'stock_warning_level' => 10, 'is_active' => true, '_who_added' => $this->staff->id,
        ]);
        // 停用的物料不该再提醒补货
        InventoryItem::create([
            'name' => '停用品', 'item_code' => 'OLD-01', 'category_id' => $category->id,
            'unit' => '盒', 'current_stock' => 0, 'stock_warning_level' => 10, 'is_active' => false, '_who_added' => $this->staff->id,
        ]);

        $rows = $this->actingAs($this->staff)
            ->getJson('/today-work/stock-warnings')
            ->assertOk()
            ->json();

        $this->assertCount(1, $rows);
        $this->assertSame($low->id, $rows[0]['id']);
        $this->assertSame('手套', $rows[0]['name']);
        $this->assertEquals(2, $rows[0]['current_stock']);
        $this->assertEquals(10, $rows[0]['warning_level']);
    }

    /** @test */
    public function 有效期预警把已过期的算成负数天(): void
    {
        $category = InventoryCategory::create(['name' => '药品', 'is_active' => true, '_who_added' => $this->staff->id]);
        $item = InventoryItem::create([
            'name' => '利多卡因', 'item_code' => 'LD-01', 'category_id' => $category->id,
            'unit' => '支', 'current_stock' => 20, 'stock_warning_level' => 5,
            'is_active' => true, 'track_expiry' => true, '_who_added' => $this->staff->id,
        ]);

        // 批次必须挂在入库单下（inventory_batches.stock_in_id 非空）
        $stockIn = StockIn::create([
            'stock_in_no'   => 'SI-' . uniqid(),
            'stock_in_date' => date('Y-m-d'),
            'status'        => 'confirmed',
            'branch_id'     => $this->staff->branch_id,
            '_who_added'    => $this->staff->id,
        ]);

        InventoryBatch::create([
            'inventory_item_id' => $item->id, 'batch_no' => 'B-EXPIRED',
            'qty' => 3, 'status' => 'available',
            'expiry_date' => date('Y-m-d', strtotime('-2 days')),
            'stock_in_id' => $stockIn->id, '_who_added' => $this->staff->id,
        ]);
        InventoryBatch::create([
            'inventory_item_id' => $item->id, 'batch_no' => 'B-SOON',
            'qty' => 5, 'status' => 'available',
            'expiry_date' => date('Y-m-d', strtotime('+10 days')),
            'stock_in_id' => $stockIn->id, '_who_added' => $this->staff->id,
        ]);
        // 30 天以外的不进预警
        InventoryBatch::create([
            'inventory_item_id' => $item->id, 'batch_no' => 'B-FAR',
            'qty' => 5, 'status' => 'available',
            'expiry_date' => date('Y-m-d', strtotime('+200 days')),
            'stock_in_id' => $stockIn->id, '_who_added' => $this->staff->id,
        ]);

        $rows = $this->actingAs($this->staff)
            ->getJson('/today-work/expiry-warnings')
            ->assertOk()
            ->json();

        $this->assertCount(2, $rows);

        $byBatch = collect($rows)->keyBy('batch_no');
        // 已过期的批次还躺在可用库存里，比"快到期"更该立刻处理 —— 前端按负数上红
        $this->assertSame(-2, $byBatch['B-EXPIRED']['days_left']);
        $this->assertSame(10, $byBatch['B-SOON']['days_left']);
        $this->assertSame('利多卡因', $byBatch['B-SOON']['name']);
    }

    /** @test */
    public function 侧栏角标把新队列都算上(): void
    {
        $this->makeAppointment(date('Y-m-d'), Appointment::STATUS_CANCELLED);
        OnlineBooking::create([
            'full_name' => '待处理', 'phone_no' => '15148368512',
            'start_date' => date('Y-m-d'), 'status' => OnlineBooking::STATUS_WAITING,
        ]);

        $counts = $this->actingAs($this->staff)
            ->getJson('/today-work/tab-counts')
            ->assertOk()
            ->json();

        $this->assertSame(1, $counts['cancelled']);
        $this->assertSame(1, $counts['online_bookings']);
        $this->assertSame(0, $counts['stock_warnings']);
        $this->assertSame(0, $counts['expiry_warnings']);
    }

    /** @test */
    public function 工作台页面上有新队列的入口(): void
    {
        $html = $this->actingAs($this->staff)->get('/today-work')->assertOk()->getContent();

        foreach (['tab-cancelled', 'tab-online-bookings',
                  'tab-stock-warnings', 'tab-expiry-warnings'] as $anchor) {
            $this->assertStringContainsString($anchor, $html);
        }
    }

    /** @test */
    public function 没有库房权限的人看不到两条库存队列(): void
    {
        $role = Role::create(['name' => 'No Stock', 'slug' => 'no-stock-queues']);
        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => Permission::where('slug', 'view-appointments')->value('id'),
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $this->staff->branch_id, 'status' => User::STATUS_ACTIVE,
        ]);

        $html = $this->actingAs($user)->get('/today-work')->assertOk()->getContent();

        $this->assertStringNotContainsString('tab-stock-warnings', $html);
        $this->assertStringNotContainsString('tab-expiry-warnings', $html);
        // 其余三条与库房无关，仍应在
        $this->assertStringContainsString('tab-cancelled', $html);
    }
}
