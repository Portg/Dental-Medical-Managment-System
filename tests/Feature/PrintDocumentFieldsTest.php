<?php

namespace Tests\Feature;

use App\Branch;
use App\Lab;
use App\LabCase;
use App\MedicalService;
use App\Patient;
use App\Quotation;
use App\QuotationItem;
use App\Role;
use App\Services\InvoicePaymentService;
use App\Services\QuotationService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * 打印单据上「字段名对不上」这一类缺陷。
 *
 * 共同点是页面和测试都发现不了：SQL 不报错、Blade 不报错，只是把 0、'-' 或
 * 原始翻译键印到给患者/技工厂的纸上。都是逐个渲染 PDF 转图看出来的。
 */
class PrintDocumentFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Branch $branch;

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
            'patient_no' => 'PD-0001',
            'surname'    => '陈',
            'othername'  => '晓雯',
            'gender'     => 'Female',
            'phone_no'   => '13800001111',
            '_who_added' => $this->user->id,
        ]);
    }

    /**
     * 报价单新建：表单提交的是 addmore[N][price]（单价），
     * 而 createQuotation() 原先读 $value['amount'] —— 不存在的键，
     * 直接抛「Column 'amount' cannot be null」，功能从来没能用过。
     */
    public function test_a_quotation_can_be_created_from_the_form_payload(): void
    {
        $service = MedicalService::create([
            'name' => '烤瓷冠修复', 'price' => 2800, '_who_added' => $this->user->id,
        ]);

        $quotation = app(QuotationService::class)->createQuotation(
            $this->patient->id,
            [['medical_service_id' => $service->id, 'qty' => 2, 'price' => 2800]],
            $this->user->id
        );

        $this->assertNotNull($quotation);

        $item = $quotation->items()->first();
        $this->assertEquals(2, $item->qty);
        $this->assertEquals(2800, $item->amount, 'amount 列存的是单价');
    }

    /**
     * API v1 按契约提交 items.*.amount（见 Api\V1\QuotationController 的校验），
     * Web 表单提交 price。两个键名都得认，修 Web 这条时别把接口弄坏。
     */
    public function test_a_quotation_can_also_be_created_from_the_api_payload(): void
    {
        $service = MedicalService::create([
            'name' => '洁牙', 'price' => 200, '_who_added' => $this->user->id,
        ]);

        $quotation = app(QuotationService::class)->createQuotation(
            $this->patient->id,
            [['medical_service_id' => $service->id, 'qty' => 1, 'amount' => 200]],
            $this->user->id
        );

        $this->assertEquals(200, $quotation->items()->first()->amount);
    }

    /**
     * 报价单打印：金额列叫 amount，视图原先读 $row->price（该列不存在），
     * 于是价格与总额整张单子全是 0。
     */
    public function test_quotation_print_shows_real_amounts(): void
    {
        $service = MedicalService::create([
            'name' => '种植体植入', 'price' => 9800, '_who_added' => $this->user->id,
        ]);

        $quotation = Quotation::create([
            'quotation_no' => 'PD-QT-1',
            'patient_id'   => $this->patient->id,
            '_who_added'   => $this->user->id,
        ]);
        QuotationItem::create([
            'quotation_id'       => $quotation->id,
            'medical_service_id' => $service->id,
            'qty'                => 3,
            'amount'             => 9800,
            '_who_added'         => $this->user->id,
        ]);

        $data = app(QuotationService::class)->getQuotationPrintData($quotation->id);
        $html = view('quotations.print_quotation', $data)->render();

        // 3 * 9800 = 29,400
        $this->assertStringContainsString('29,400', $html, '行小计应为 qty * amount');
        $this->assertStringContainsString('9,800', $html, '单价应显示出来');
        // 打印页用 $patient->full_name（Patient 上的访问器），取数必须给模型
        // 而不是 DB::table 的 stdClass，否则报价单上的患者姓名是空的
        $this->assertStringContainsString('陈晓雯', $html, '报价单上要能看到患者姓名');
    }

    /**
     * 技工单：医生栏读的是 $labCase->doctor->name，而 User 没有 name 属性
     * （姓名是 surname + othername），导致每张单子的医生恒为 '-'。
     * 义齿类型为空时还会把原始翻译键 lab_cases.type_ 印在纸上。
     */
    public function test_lab_case_print_shows_doctor_and_no_raw_translation_key(): void
    {
        $lab = Lab::create([
            'name' => '上海精工义齿加工厂', 'contact' => '李经理',
            'phone' => '021-66668888', 'is_active' => 1, '_who_added' => $this->user->id,
        ]);

        $labCase = LabCase::create([
            'lab_case_no'          => 'PD-LC-1',
            'patient_id'           => $this->patient->id,
            'doctor_id'            => $this->user->id,
            'lab_id'               => $lab->id,
            'status'               => 'sent',
            'sent_date'            => now()->subDays(2),
            'expected_return_date' => now()->addDays(3),
            'lab_fee'              => 1200,
            '_who_added'           => $this->user->id,
        ]);

        $labCase->load(['patient', 'doctor', 'lab']);
        $html = view('lab_cases.print', compact('labCase'))->render();

        $this->assertStringContainsString('关立亚', $html, '医生姓名应显示（User 没有 name 属性）');
        $this->assertStringNotContainsString('lab_cases.type_', $html, '不能把原始翻译键印到技工单上');
        $this->assertStringNotContainsString('00:00:00', $html, '日期不该带时分秒');
    }

    /**
     * 收据：payment_method 库里存的是 Cash / Self Account 这类英文枚举值，
     * 直出会让给患者的收据上印一行英文。
     */
    public function test_payment_method_is_shown_in_chinese(): void
    {
        $this->assertSame('现金', InvoicePaymentService::methodLabel('Cash'));
        $this->assertSame('储值卡', InvoicePaymentService::methodLabel('StoredValue'));
        $this->assertSame('-', InvoicePaymentService::methodLabel(null));

        // 枚举外的值原样返回，好过显示空白
        $this->assertSame('SomeGateway', InvoicePaymentService::methodLabel('SomeGateway'));
    }

    /**
     * 收据模板不许再直出原始枚举值或未格式化的时间戳。
     */
    public function test_receipt_template_formats_method_and_dates(): void
    {
        $view = File::get(resource_path('views/invoices/receipt_print.blade.php'));

        $this->assertStringNotContainsString(
            '{{ $row->payment_method }}',
            $view,
            '直出 payment_method 会在收据上印出英文枚举值'
        );
        $this->assertStringNotContainsString(
            '{{ $invoice->created_at }}',
            $view,
            '直接 echo Carbon 会带出秒'
        );
    }

    /**
     * 四个 Service 都用 DB::table 取患者，返回 stdClass；而对应视图用的是
     * $patient->full_name —— Patient 上的访问器，裸查询取不到。
     *
     * 后果分两种：打印单据上患者姓名是空的；报价单详情页在 APP_DEBUG 下
     * 因 Undefined property 直接 500（浏览器里打不开，tinker 里只是个警告，
     * 所以一直没被发现）。
     */
    public function test_services_return_patient_models_so_the_full_name_accessor_works(): void
    {
        $service = MedicalService::create([
            'name' => '洁牙', 'price' => 200, '_who_added' => $this->user->id,
        ]);

        $quotation = app(QuotationService::class)->createQuotation(
            $this->patient->id,
            [['medical_service_id' => $service->id, 'qty' => 1, 'price' => 200]],
            $this->user->id
        );

        $appointment = \App\Appointment::create([
            'appointment_no'    => 'PD-APT-1',
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

        $invoice = \App\Invoice::create([
            'invoice_no'   => 'PD-INV-1',
            'invoice_date' => now()->toDateString(),
            'total_amount' => 200,
            'paid_amount'  => 0,
            'patient_id'   => $this->patient->id,
            'branch_id'    => $this->branch->id,
            '_who_added'   => $this->user->id,
        ]);

        $cases = [
            '报价单详情' => app(QuotationService::class)->getQuotationShowData($quotation->id)['patient'],
            '报价单打印' => app(QuotationService::class)->getQuotationPrintData($quotation->id)['patient'],
            '处方打印'   => app(\App\Services\PrescriptionService::class)
                              ->getPrintDataByAppointment($appointment->id)['patient'],
            '账单详情'   => app(\App\Services\InvoiceService::class)
                              ->getInvoiceDetail($invoice->id)['patient'],
        ];

        foreach ($cases as $label => $patient) {
            $this->assertInstanceOf(
                Patient::class,
                $patient,
                "{$label} 返回的必须是模型，stdClass 上没有 full_name 访问器"
            );
            $this->assertSame('陈晓雯', $patient->full_name, "{$label} 取不到患者姓名");
        }
    }
}
