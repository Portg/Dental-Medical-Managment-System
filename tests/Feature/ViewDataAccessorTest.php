<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\MedicalCard;
use App\MedicalService;
use App\Patient;
use App\Prescription;
use App\Quotation;
use App\QuotationItem;
use App\Role;
use App\Services\InvoiceService;
use App\Services\MedicalCardService;
use App\Services\PrescriptionService;
use App\Services\QuotationService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 交给视图的对象必须支撑视图用到的访问器。
 *
 * 这是本轮反复踩的同一个坑：Service 用 DB::table()->first() 返回 stdClass，
 * 而视图写 $patient->full_name / $prescribed_by->full_name —— 那是模型上的
 * 访问器（Patient::getFullNameAttribute / User::getFullNameAttribute），
 * 裸查询结果上根本没有。
 *
 * 恶心之处在于它不报错：PHP 对未定义属性只发 Warning，CLI 与 tinker 里照常
 * 往下跑，PDF 照生成、页面照渲染，只是姓名那一格是空的。要么在 APP_DEBUG
 * 打开的浏览器里被转成异常整页 500，要么就一直没人发现。
 *
 * 我修了三轮才扫干净：先是打印路径，再是详情页，最后才发现处方的
 * prescribed_by 也是。所以这里把七条路径一次性钉住，别再逐个漏。
 *
 * 例外：InvoiceService::getReceiptData() 与 sendInvoiceEmail() 仍返回 stdClass，
 * 但它们在 SQL 里用 CONCAT(...) as full_name 把值拼好了，能用。本用例按
 * 「取得到 full_name」断言而不是「必须是模型」，正是为了容纳这种写法。
 */
class ViewDataAccessorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Branch $branch;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        $this->user = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $this->branch->id,
            'surname'   => '关',
            'othername' => '立亚',
            'status'    => 'active',
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'VA-0001',
            'surname'    => '陈',
            'othername'  => '晓雯',
            'gender'     => 'Female',
            'phone_no'   => '13800001111',
            '_who_added' => $this->user->id,
        ]);

        $this->appointment = Appointment::create([
            'appointment_no'    => 'VA-APT-1',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->user->id,
            'branch_id'         => $this->branch->id,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'start_time'        => '10:00 AM',
            'visit_information' => 'appointment',
            '_who_added'        => $this->user->id,
            'sort_by'           => now()->toDateString() . ' 10:00:00',
        ]);
    }

    /**
     * 七条会把患者交给视图的路径，full_name 都要取得到。
     */
    public function test_every_patient_bearing_view_payload_resolves_full_name(): void
    {
        $service = MedicalService::create([
            'name' => '烤瓷冠修复', 'price' => 2800, '_who_added' => $this->user->id,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'VA-INV-1',
            'invoice_date' => now()->toDateString(),
            'total_amount' => 2800,
            'paid_amount'  => 0,
            'patient_id'   => $this->patient->id,
            'branch_id'    => $this->branch->id,
            '_who_added'   => $this->user->id,
        ]);

        $quotation = Quotation::create([
            'quotation_no' => 'VA-QT-1',
            'patient_id'   => $this->patient->id,
            '_who_added'   => $this->user->id,
        ]);
        QuotationItem::create([
            'quotation_id'       => $quotation->id,
            'medical_service_id' => $service->id,
            'qty'                => 1,
            'amount'             => 2800,
            '_who_added'         => $this->user->id,
        ]);

        $card = MedicalCard::create([
            'patient_id' => $this->patient->id,
            '_who_added' => $this->user->id,
        ]);

        Prescription::create([
            'appointment_id' => $this->appointment->id,
            'drug'           => '阿莫西林胶囊',
            'qty'            => '21 粒',
            'directions'     => '口服，每日三次',
            'status'         => 'pending',
            '_who_added'     => $this->user->id,
        ]);

        $payloads = [
            '收据打印'   => app(InvoiceService::class)->getReceiptData($invoice->id)['patient'],
            '账单详情'   => app(InvoiceService::class)->getInvoiceDetail($invoice->id)['patient'],
            '报价单详情' => app(QuotationService::class)->getQuotationShowData($quotation->id)['patient'],
            '报价单打印' => app(QuotationService::class)->getQuotationPrintData($quotation->id)['patient'],
            '会诊卡详情' => app(MedicalCardService::class)->getMedicalCardDetail($card->id)['patient'],
            '处方打印'   => app(PrescriptionService::class)
                                ->getPrintDataByAppointment($this->appointment->id)['patient'],
        ];

        foreach ($payloads as $label => $patient) {
            $this->assertNotNull($patient, "{$label} 没拿到患者");
            $this->assertSame(
                '陈晓雯',
                $patient->full_name ?? null,
                "{$label} 取不到 full_name —— 多半又是 DB::table 返回了 stdClass"
            );
        }
    }

    /**
     * 处方笺的开方医生走 User 上的 full_name 访问器，同样不能是 stdClass。
     */
    public function test_prescription_print_resolves_the_prescriber_name(): void
    {
        Prescription::create([
            'appointment_id' => $this->appointment->id,
            'drug'           => '甲硝唑片',
            'qty'            => '12 片',
            'directions'     => '口服，每日两次',
            'status'         => 'pending',
            '_who_added'     => $this->user->id,
        ]);

        $prescribedBy = app(PrescriptionService::class)
            ->getPrintDataByAppointment($this->appointment->id)['prescribed_by'];

        $this->assertNotNull($prescribedBy, '开方医生为空，处方笺上那一栏会是空白');
        $this->assertSame('关立亚', $prescribedBy->full_name ?? null);
    }
}
