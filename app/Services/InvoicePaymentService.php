<?php

namespace App\Services;

use App\Invoice;
use App\InvoicePayment;
use App\MemberTransaction;
use App\Refund;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\SystemSetting;
use App\Services\MemberService;

class InvoicePaymentService
{
    /** 储值支付要动患者余额，几条路径都要特判，别再写字面量 */
    public const METHOD_STORED_VALUE = 'StoredValue';

    /**
     * Supported payment methods (PRD 4.1.3).
     */
    public const PAYMENT_METHODS = [
        'Cash' => ['label' => 'invoices.cash', 'fee' => 0],
        'WeChat' => ['label' => 'invoices.wechat_pay', 'fee' => 0.006],
        'Alipay' => ['label' => 'invoices.alipay', 'fee' => 0.006],
        'BankCard' => ['label' => 'invoices.bank_card', 'fee' => 0.005],
        'StoredValue' => ['label' => 'invoices.stored_value', 'fee' => 0],
        'Insurance' => ['label' => 'invoices.insurance', 'fee' => 0],
        'Online Wallet' => ['label' => 'invoices.online_wallet', 'fee' => 0],
        'Mobile Money' => ['label' => 'invoices.mobile_money', 'fee' => 0],
        'Cheque' => ['label' => 'invoices.cheque', 'fee' => 0],
        'Self Account' => ['label' => 'invoices.self_account', 'fee' => 0],
        'Credit' => ['label' => 'invoices.credit', 'fee' => 0],
    ];

    /**
     * 收款方式的可读名称。
     *
     * 库里存的是 'Cash' / 'Self Account' 这类英文枚举值，直接输出到单据上就是
     * 一行英文（收据打印页此前就是这样）。映射本来就在 PAYMENT_METHODS 里，
     * 这里包一层，省得每个视图各自写一份数组。
     *
     * 认不出的值原样返回：服务商回执或历史数据可能带来枚举外的字符串，
     * 显示原值也好过显示空白。
     */
    public static function methodLabel(?string $method): string
    {
        if ($method === null || $method === '') {
            return '-';
        }

        $key = self::PAYMENT_METHODS[$method]['label'] ?? null;

        return $key === null ? $method : __($key);
    }

    /**
     * Get payments for an invoice.
     */
    public function getPaymentsByInvoice(int $invoiceId): Collection
    {
        return InvoicePayment::where('invoice_id', $invoiceId)->get();
    }

    /**
     * Get payment with insurance company info.
     */
    public function getPaymentForEdit(int $id): ?object
    {
        return DB::table('invoice_payments')
            ->leftJoin('insurance_companies', 'insurance_companies.id',
                'invoice_payments.insurance_company_id')
            ->leftJoin('self_accounts', 'self_accounts.id',
                'invoice_payments.self_account_id')
            ->where('invoice_payments.id', $id)
            ->select('invoice_payments.*', 'insurance_companies.name', 'self_accounts.account_holder as self_account_name')
            ->first();
    }

    /**
     * 一笔收款明细的校验规则，四个入口共用。
     *
     * 此前每个入口各写各的：Web 单笔补齐了白名单与条件字段，Web 混合支付、
     * API 的单笔与混合支付却仍只校验 payment_method 是字符串 —— 于是同一条
     * 业务规则在一个入口拦得住、换个入口就绕过去了：任意付款方式、没有支票号
     * 的支票、没有保险公司的保险收款都能落库。规则集中在这里，加一种付款方式
     * 或多一个必填项时不会再漏改某个入口。
     *
     * @param string $prefix 混合支付传 'payments.*.'；required_if 里的 *
     *                       由 Laravel 按当前下标替换成同一笔的 payment_method
     */
    public static function detailRules(string $prefix = ''): array
    {
        $method = $prefix . 'payment_method';

        return [
            $method => ['required', 'string', Rule::in(array_keys(self::PAYMENT_METHODS))],
            $prefix . 'cheque_no' => "required_if:{$method},Cheque|nullable|string|max:100",
            $prefix . 'bank_name' => "required_if:{$method},Cheque|nullable|string|max:255",
            $prefix . 'account_name' => 'nullable|string|max:255',
            $prefix . 'insurance_company_id' => "required_if:{$method},Insurance|nullable|exists:insurance_companies,id",
            $prefix . 'self_account_id' => "required_if:{$method},Self Account|nullable|exists:self_accounts,id",
        ];
    }

    /**
     * Create a new payment record.
     *
     * 与 processMixedPayment() 走同一套前置判断：锁住账单、确认可收款、确认不超收。
     * 此前这条路径三样都没有 —— 欠款 1000 的账单能登记 1500，折扣还没审批也照收，
     * 两笔并发的单笔收款同样能一起挤进来。超收只能靠退费纠正，而退费要走审批。
     *
     * @throws \RuntimeException 账单不存在、折扣待审批、或金额超过欠款
     */
    public function createPayment(array $data): ?InvoicePayment
    {
        return DB::transaction(function () use ($data) {
            $invoiceId = (int) $data['invoice_id'];

            $invoice = Invoice::where('id', $invoiceId)->lockForUpdate()->first();

            if (!$invoice) {
                throw new \RuntimeException(__('messages.record_not_found'));
            }

            if (!$invoice->canAcceptPayment()) {
                throw new \RuntimeException(__('invoices.discount_approval_required'));
            }

            if (bccomp((string) $data['amount'], $this->outstandingFor($invoice), 2) > 0) {
                throw new \RuntimeException(__('invoices.payment_exceeds_outstanding'));
            }

            $payment = InvoicePayment::create([
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'cheque_no' => $data['cheque_no'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $data['insurance_company_id'] ?? null,
                'self_account_id' => $data['self_account_id'] ?? null,
                'branch_id' => Auth::User()->branch_id,
                '_who_added' => Auth::User()->id,
            ]);

            // 储值支付要真的从患者卡里扣钱。放在插入之后、重算之前：
            // 余额不足会抛异常，整个事务连收款记录一起回滚。
            if (($data['payment_method'] ?? null) === self::METHOD_STORED_VALUE) {
                $this->chargeStoredValue($invoice, (string) $data['amount'], $payment);
            }

            // 积分与累计消费与混合支付同一段逻辑，别再让「走哪个入口」决定患者拿不拿得到积分
            $this->awardMemberBenefits($invoice, $payment);
            $this->checkMemberUpgrade($invoice->patient);

            $this->syncInvoicePaidAmount($invoiceId);

            return $payment;
        });
    }

    /**
     * Update an existing payment record.
     *
     * 与 createPayment() 同样要锁账单、看折扣审批、卡超收 —— 少了这三样，
     * 「把已有的 500 改成 1500」就是一条绕过所有收款校验的后门，
     * API 的 update 恰好允许传新金额。判超收时要先把这笔自己的旧金额摘出去，
     * 否则改小金额也会被自己挡住。
     *
     * @throws \RuntimeException 折扣待审批、金额超过欠款、或改动涉及储值支付
     */
    public function updatePayment(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $payment = InvoicePayment::where('id', $id)->lockForUpdate()->first();

            if (!$payment) {
                return false;
            }

            $invoice = Invoice::where('id', $payment->invoice_id)->lockForUpdate()->first();

            if (!$invoice) {
                return false;
            }

            $this->assertNotStoredValueEdit($payment, $data['payment_method'] ?? null);

            if (!$invoice->canAcceptPayment()) {
                throw new \RuntimeException(__('invoices.discount_approval_required'));
            }

            $newAmount = (string) ($data['amount'] ?? $payment->amount);

            // 别的明细加起来还剩多少额度：总额 −（当前实收 − 这笔自己的旧金额）
            $others = bcsub($this->netPaidFor((int) $invoice->id), (string) $payment->amount, 2);
            $room = bcsub((string) $invoice->total_amount, $others, 2);

            if (bccomp($newAmount, $room, 2) > 0) {
                throw new \RuntimeException(__('invoices.payment_exceeds_outstanding'));
            }

            $updated = (bool) InvoicePayment::where('id', $id)->update([
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'cheque_no' => $data['cheque_no'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'insurance_company_id' => $data['insurance_company_id'] ?? null,
                'self_account_id' => $data['self_account_id'] ?? null,
                'branch_id' => Auth::User()->branch_id,
                '_who_added' => Auth::User()->id,
            ]);

            // 改金额同样要让账单跟上 —— 改收款方式的弹窗虽然只提交方式，
            // 但控制器允许带 amount，别把这条路径漏了。
            if ($updated) {
                // 累计消费与积分是按「这笔收多少、什么方式」算出来的，改了就得重算：
                // 收 500 后改成 100，不重算的话累计消费还停在 500，之后撤销只减 100，
                // 患者账上白留 400。先按原值冲销、再按新值发放，两步都在同一个事务里。
                $fresh = InvoicePayment::find($id);

                $this->reverseMemberBenefits($payment);
                $this->awardMemberBenefits($invoice, $fresh);
                $this->checkMemberUpgrade($invoice->patient);

                $this->syncInvoicePaidAmount((int) $invoice->id);
            }

            return $updated;
        });
    }

    /**
     * 储值支付不允许就地改。
     *
     * 改金额要同步调余额、改方式要一进一出，两边都得再写会员流水；就地改等于
     * 让一条流水对应两种事实，对账时说不清哪笔钱去了哪儿。正确做法是撤销原收款
     * （余额会退回去，见 deletePayment）再重新登记一笔。
     */
    private function assertNotStoredValueEdit(InvoicePayment $payment, ?string $newMethod): void
    {
        $wasStoredValue = $payment->payment_method === self::METHOD_STORED_VALUE;
        $becomesStoredValue = $newMethod === self::METHOD_STORED_VALUE;

        if ($wasStoredValue || $becomesStoredValue) {
            throw new \RuntimeException(__('invoices.stored_value_payment_not_editable'));
        }
    }

    /**
     * Delete a payment record.
     *
     * 账单上只要挂着退费（待审批或已通过），就不许再撤销收款。
     *
     * 撤销走的是「按这笔付款的全额退回」，而退费退的是账单层面的金额，两者没有
     * 分摊关系：储值支付 300 → 退费 100 → 再撤销那笔付款，患者卡里一共多出 400，
     * 凭空多了 100。非储值的方式虽然不动外部余额，但同样会算出「实收为负、
     * 钱却已经退出去了」的账。
     *
     * 待审批的也要挡：先撤销收款、再去把那张待审批的退费单批掉，钱一样会退出去，
     * 只是把重复入账推迟到审批那一刻。审批侧另有一道复核（RefundService 会重新
     * 核对可退金额），两道一起才堵得住。要调整先处理退费单，顺序反过来才说得清。
     *
     * @throws \RuntimeException 账单挂着未处理或已通过的退费
     */
    public function deletePayment(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $payment = InvoicePayment::where('id', $id)->lockForUpdate()->first();

            if (!$payment) {
                return false;
            }

            $invoiceId = (int) $payment->invoice_id;

            $hasOpenRefund = Refund::where('invoice_id', $invoiceId)
                ->whereIn('approval_status', [Refund::APPROVAL_PENDING, Refund::APPROVAL_APPROVED])
                ->exists();

            if ($hasOpenRefund) {
                throw new \RuntimeException(__('invoices.payment_locked_by_refund'));
            }

            $deleted = (bool) InvoicePayment::where('id', $id)->delete();

            if (!$deleted) {
                return false;
            }

            // 撤销储值收款要把钱退回患者卡里，否则那笔余额就凭空消失了
            if ($payment->payment_method === self::METHOD_STORED_VALUE) {
                $this->refundStoredValue($payment);
            }

            // 这笔当初给出去的积分与累计消费一并冲销，否则「收了再撤」就是白拿积分
            $this->reverseMemberBenefits($payment);

            if ($invoiceId > 0) {
                $this->syncInvoicePaidAmount($invoiceId);
            }

            return $deleted;
        });
    }

    /**
     * 按明细重算账单的已收金额，并连带刷新欠款与付款状态。
     *
     * 为什么是「重算」而不是「增减」：
     *   invoice.paid_amount 是存储列，outstanding_amount 与 payment_status 都由
     *   Invoice::boot() 的 saving 钩子据它派生。此前只有 processMixedPayment() 会
     *   bcadd 一次，走 /payments 的单笔收款、改金额、撤销收款统统不碰它 —— 账单
     *   列表靠子查询算 computed_paid 才显示对，而存储的 payment_status 一直停在
     *   「未付」，凡是按这个字段筛的地方（欠费报表、催收）就都不准。
     *   增量加减一旦漂就再也回不来；按明细重算能自愈，且与列表的 computed_paid
     *   同源，两边不会再各说各话。
     *
     * 公式是「付款合计 − 已通过退费合计」：退费通过时 RefundService::executeRefund()
     * 会把退款从 paid_amount 里扣掉，只按付款求和会把这笔扣减抹平，已退费的账单
     * 会跳回全额已付。
     *
     * InvoicePayment 与 Refund 都用了 SoftDeletes，撤销的记录自动不计入。
     *
     * 「读明细 → 求和 → 写回账单」这三步必须在一个事务里、并且先把账单行锁住。
     * 否则两笔并发收款会这样交错：
     *   A 插入 100 → A 求和得 100 → B 插入 200 → B 求和得 300 → B 写 300 → A 写 100
     * 后落地的是 A 那份过期的汇总，账单的已收金额就停在 100，payment_status 跟着
     * 一起错 —— 正是这个方法本来要根治的那种漂移，只是换成了并发触发。
     * 退费审批与撤销收款同时发生也是同一条路径。
     */
    public function syncInvoicePaidAmount(int $invoiceId): void
    {
        // processMixedPayment() 已经开了事务，这里会退化成 savepoint，不会嵌套报错。
        DB::transaction(function () use ($invoiceId) {
            // lockForUpdate 让并发的重算排队：拿到锁之后再求和，读到的一定是
            // 对方已提交的全部明细。SQLite（测试）没有行锁，但也没有并发写。
            $invoice = Invoice::where('id', $invoiceId)->lockForUpdate()->first();

            if (!$invoice) {
                return;
            }

            $invoice->paid_amount = $this->netPaidFor($invoiceId);
            $invoice->save();
        });
    }

    /**
     * 一笔收款该给的累计消费与会员积分。
     *
     * 原先只有 processMixedPayment() 会做这件事，而且是「整批加总记一条流水」：
     *   - 同样一笔 500 元现金，走 /payments/mixed 有积分，走 /payments 没有；
     *   - 撤销收款时积分不回退，一笔一笔地攒出「免费积分」。
     * 现在两条路径共用这一段，并按**笔**记流水（invoice_payment_id），
     * 撤销时才能反查出这一笔当初真给了多少，而不是拿费率重算 ——
     * 会员等级中途变过的话，重算出来的数跟当初就对不上了。
     */
    private function awardMemberBenefits(Invoice $invoice, InvoicePayment $payment): void
    {
        if (!$invoice->patient_id) {
            return;
        }

        // 锁住患者行再读写累计消费与积分：同一患者同时结两张现金账单时，
        // 「读 → 加 → 存」不锁会互相覆盖，只累计到其中一笔。
        $patient = \App\Patient::where('id', $invoice->patient_id)->lockForUpdate()->first();

        if (!$patient) {
            return;
        }

        $amount = (string) $payment->amount;

        $patient->total_consumption = bcadd((string) ($patient->total_consumption ?? 0), $amount, 2);
        $patient->save();

        // 打上标记：撤销时据此判断这笔当初到底发没发过，
        // 历史收款（本次改动之前建的）保持 false，不会被错误冲减。
        $payment->member_benefits_awarded = true;
        $payment->save();

        if (!$patient->memberLevel || !SystemSetting::get('member.points_enabled', true)) {
            return;
        }

        $rate = $patient->memberLevel->getPointsRateForMethod($payment->payment_method);
        $points = (int) floor((float) $amount * $rate);

        if ($points <= 0) {
            return;
        }

        $patient->member_points = ($patient->member_points ?? 0) + $points;
        $patient->save();

        $expiryDays = (int) SystemSetting::get('member.points_expiry_days', 0);

        MemberTransaction::create([
            'transaction_no'     => MemberTransaction::generateTransactionNo(),
            'transaction_type'   => 'Points',
            'patient_id'         => $patient->id,
            'amount'             => 0,
            'balance_before'     => $patient->member_balance,
            'balance_after'      => $patient->member_balance,
            'points_change'      => $points,
            'points_expires_at'  => $expiryDays > 0 ? now()->addDays($expiryDays)->toDateString() : null,
            'description'        => __('members.type_points') . ' +' . $points,
            'invoice_id'         => $invoice->id,
            'invoice_payment_id' => $payment->id,
            '_who_added'         => Auth::id(),
        ]);
    }

    /**
     * 撤销一笔收款时，把这笔给出去的累计消费与积分冲销掉。
     *
     * 积分按当初那条流水的 points_change 冲，不重算 —— 见 awardMemberBenefits()。
     * 积分与累计消费都不允许被冲成负数：历史数据里有先积分后手工调整的情况，
     * 硬减会把患者的账户改成负值，比少冲一点更难解释。
     *
     * 只冲 member_benefits_awarded 为真的收款。本次改动之前，走 /payments 的单笔
     * 收款从来不加累计消费；不看这个标记就会把患者原本就有的累计消费白白减掉，
     * 严重时还会把会员等级降下去。
     */
    private function reverseMemberBenefits(InvoicePayment $payment): void
    {
        if (!$payment->member_benefits_awarded) {
            return;
        }

        $invoice = Invoice::find($payment->invoice_id);

        if (!$invoice || !$invoice->patient_id) {
            return;
        }

        // 与发放同样要锁患者行，理由见 awardMemberBenefits()
        $patient = \App\Patient::where('id', $invoice->patient_id)->lockForUpdate()->first();

        if (!$patient) {
            return;
        }

        $amount = (string) $payment->amount;

        $consumption = bcsub((string) ($patient->total_consumption ?? 0), $amount, 2);
        $patient->total_consumption = bccomp($consumption, '0', 2) >= 0 ? $consumption : '0';

        $awarded = (int) MemberTransaction::where('invoice_payment_id', $payment->id)
            ->where('transaction_type', 'Points')
            ->sum('points_change');

        if ($awarded > 0) {
            $patient->member_points = max(0, (int) ($patient->member_points ?? 0) - $awarded);
        }

        $patient->save();

        if ($awarded > 0) {
            MemberTransaction::create([
                'transaction_no'     => MemberTransaction::generateTransactionNo(),
                'transaction_type'   => 'Points',
                'patient_id'         => $patient->id,
                'amount'             => 0,
                'balance_before'     => $patient->member_balance,
                'balance_after'      => $patient->member_balance,
                'points_change'      => -$awarded,
                'description'        => __('members.type_points') . ' -' . $awarded,
                'invoice_id'         => $invoice->id,
                'invoice_payment_id' => $payment->id,
                '_who_added'         => Auth::id(),
            ]);
        }
    }

    /**
     * 消费之后复核会员等级。累计消费变过就该看一眼有没有升档。
     */
    private function checkMemberUpgrade(?\App\Patient $patient): void
    {
        if ($patient && $patient->member_level_id) {
            $patient->refresh();
            app(MemberService::class)->checkAndUpgrade($patient);
        }
    }

    /**
     * 取出该扣款/退款的那张卡，并把这一行锁住。
     *
     * 共享卡扣的是主卡持有人的余额，所以先 resolvePrimaryMember 再按主卡 id 上锁。
     *
     * 锁是必须的：「读余额 → 判够不够 → 写回」不锁的话，同一个会员同时结两张账单
     * 会这样交错 —— 两边都读到 2000、都判定够付 300、都写回 1700，卡里少扣了一笔
     * 300。余额是真金白银，漂了只能靠人工盘。RefundService::executeRefund() 早就
     * 是 lockForUpdate 了，这里当时漏了。
     */
    private function lockPayingMember(int $patientId): \App\Patient
    {
        $primaryId = app(MemberService::class)->resolvePrimaryMember($patientId)->id;

        return \App\Patient::where('id', $primaryId)->lockForUpdate()->firstOrFail();
    }

    /**
     * 从患者储值余额里扣一笔，并留下会员流水。
     *
     * 此前这段只长在 processMixedPayment() 里，于是「谁扣余额」取决于走哪个入口：
     * API 单笔收款选储值、把现金改成储值，都只写收款记录不动余额；撤销一笔储值
     * 收款也不退回余额。账单上写着「储值已付」，患者的卡里却分文未动 —— 对账时
     * 两边永远合不上。抽出来之后，四条路径共用同一段。
     *
     * 共享卡：扣的是主卡持有人的余额（resolvePrimaryMember）。
     *
     * @throws \RuntimeException 账单没有关联患者，或余额不足
     */
    private function chargeStoredValue(Invoice $invoice, string $amount, ?InvoicePayment $payment = null): void
    {
        $patient = $invoice->patient;

        if (!$patient) {
            throw new \RuntimeException(__('invoices.patient_required_for_stored_value'));
        }

        $payingPatient = $this->lockPayingMember($patient->id);
        $balanceBefore = (string) ($payingPatient->member_balance ?? 0);

        if (bccomp($amount, $balanceBefore, 2) > 0) {
            throw new \RuntimeException(__('invoices.insufficient_stored_balance'));
        }

        $payingPatient->member_balance = bcsub($balanceBefore, $amount, 2);
        $payingPatient->save();

        MemberTransaction::create([
            'transaction_no' => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Consumption',
            'patient_id' => $payingPatient->id,
            'amount' => bcmul($amount, '-1', 2),
            'balance_before' => $balanceBefore,
            'balance_after' => $payingPatient->member_balance,
            'description' => __('invoices.stored_value_payment', ['invoice_no' => $invoice->invoice_no]),
            'invoice_id' => $invoice->id,
            'invoice_payment_id' => $payment?->id,
            '_who_added' => Auth::id(),
        ]);
    }

    /**
     * 撤销一笔储值收款时把余额退回去，并留下反向流水。
     *
     * 没有这一步的话，撤销收款只是把账单的已收金额减掉，患者卡里那笔钱就凭空
     * 消失了 —— 而且是无声的，患者下次消费才会发现余额不对。
     *
     * 退回哪张卡，以**当初扣款那条流水**记下的 patient_id 为准，而不是现在重新
     * 解析共享卡关系：付款之后共享卡可能被解绑或改绑，按当前关系退会把钱退给
     * 另一个人 —— 一个人凭空多一笔，另一个人凭空少一笔。
     */
    private function refundStoredValue(InvoicePayment $payment): void
    {
        $invoice = Invoice::find($payment->invoice_id);

        // 当初扣的就是这个人，认它
        $chargedPatientId = MemberTransaction::where('invoice_payment_id', $payment->id)
            ->where('transaction_type', 'Consumption')
            ->value('patient_id');

        // 没有流水的只可能是本次改动之前的历史记录，退而求其次按当前关系解析
        $payingPatientId = $chargedPatientId
            ?: ($invoice?->patient_id
                ? app(MemberService::class)->resolvePrimaryMember((int) $invoice->patient_id)->id
                : null);

        if (!$payingPatientId) {
            // 账单或患者已经不在了，退无可退；记一笔日志好过静默吞掉
            Log::warning('Stored-value payment reversed without a patient to credit', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
            ]);

            return;
        }

        $payingPatient = \App\Patient::where('id', $payingPatientId)->lockForUpdate()->first();

        if (!$payingPatient) {
            Log::warning('Stored-value payment reversed but the paying patient is gone', [
                'payment_id' => $payment->id,
                'patient_id' => $payingPatientId,
            ]);

            return;
        }
        $balanceBefore = (string) ($payingPatient->member_balance ?? 0);
        $amount = (string) $payment->amount;

        $payingPatient->member_balance = bcadd($balanceBefore, $amount, 2);
        $payingPatient->save();

        MemberTransaction::create([
            'transaction_no' => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Refund',
            'patient_id' => $payingPatient->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $payingPatient->member_balance,
            'description' => __('invoices.stored_value_payment_reversed', ['invoice_no' => $invoice?->invoice_no]),
            'invoice_id' => $invoice?->id,
            'invoice_payment_id' => $payment->id,
            '_who_added' => Auth::id(),
        ]);
    }

    /**
     * 按明细算出的实收：付款合计 − 已通过退费合计，下限 0。
     */
    private function netPaidFor(int $invoiceId): string
    {
        $paid = (string) InvoicePayment::where('invoice_id', $invoiceId)->sum('amount');

        $refunded = (string) Refund::where('invoice_id', $invoiceId)
            ->where('approval_status', Refund::APPROVAL_APPROVED)
            ->sum('refund_amount');

        $net = bcsub($paid, $refunded, 2);

        return bccomp($net, '0', 2) >= 0 ? $net : '0';
    }

    /**
     * 按明细算出的欠款，用于「这笔收款超没超」的判断。
     *
     * 不读 invoices.outstanding_amount 那一列：它由 paid_amount 派生，而
     * paid_amount 正是可能漂掉的那个值（历史数据里就有账单记着 900、明细一条
     * 都没有的情况）。拿漂掉的欠款去卡收款，会把本该收得下的钱拦掉；按明细算
     * 与 syncInvoicePaidAmount() 同源，判断和写回用的是同一个事实。
     */
    private function outstandingFor(Invoice $invoice): string
    {
        $outstanding = bcsub((string) $invoice->total_amount, $this->netPaidFor((int) $invoice->id), 2);

        return bccomp($outstanding, '0', 2) >= 0 ? $outstanding : '0';
    }

    /**
     * Process mixed payment (multiple methods for one invoice).
     *
     * @return array{status: bool, message: string, paid_amount?: float, change_due?: float, new_balance?: float}
     */
    public function processMixedPayment(int $invoiceId, array $payments, ?string $paymentDate = null): array
    {
        // 账单不存在是 404 级别的错误，不必进事务
        Invoice::findOrFail($invoiceId);

        // AG-065: bcmath for all monetary accumulation
        $totalPayment = array_reduce(
            $payments,
            fn ($carry, $p) => bcadd($carry, (string) ($p['amount'] ?? 0), 2),
            '0'
        );

        DB::beginTransaction();
        try {
            // 读欠款必须在事务里、并且先锁住账单行。
            //
            // 原先「先读 outstanding_amount 判断超没超，再开事务插收款」中间是敞开的：
            // 欠款 1000 的账单上两笔并发的 600 元各自都读到 1000、各自都判定没超，
            // 最后收进 1200 —— 超收要靠退费才能纠正，而退费得走审批。
            // 锁放在这里，后到的那笔会等前一笔提交后再读，读到的欠款是 400，当场被拦。
            $invoice = Invoice::where('id', $invoiceId)->lockForUpdate()->firstOrFail();

            if (!$invoice->canAcceptPayment()) {
                DB::rollBack();

                return ['status' => false, 'message' => __('invoices.discount_approval_required')];
            }

            // 按明细算欠款，与 createPayment() 同源 —— 见 outstandingFor() 的注释
            $outstanding = $this->outstandingFor($invoice);

            if (bccomp($totalPayment, $outstanding, 2) > 0) {
                DB::rollBack();

                return ['status' => false, 'message' => __('invoices.payment_exceeds_outstanding')];
            }

            $paymentDate = $paymentDate ?? now()->format('Y-m-d');
            $patient = $invoice->patient;

            foreach ($payments as $paymentData) {
                $method = $paymentData['payment_method'];
                $amount = $paymentData['amount'];

                if ($amount <= 0) continue;

                $created = InvoicePayment::create([
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $method,
                    'cheque_no' => $paymentData['cheque_no'] ?? null,
                    'account_name' => $paymentData['account_name'] ?? null,
                    'bank_name' => $paymentData['bank_name'] ?? null,
                    'invoice_id' => $invoice->id,
                    'insurance_company_id' => $paymentData['insurance_company_id'] ?? null,
                    'self_account_id' => $paymentData['self_account_id'] ?? null,
                    'transaction_ref' => $paymentData['transaction_ref'] ?? null,
                    'branch_id' => Auth::user()->branch_id ?? null,
                    '_who_added' => Auth::id(),
                ]);

                if ($method === self::METHOD_STORED_VALUE) {
                    $this->chargeStoredValue($invoice, (string) $amount, $created);
                }

                // 积分与累计消费按笔记，与单笔收款同一段逻辑
                $this->awardMemberBenefits($invoice, $created);
            }

            // 与单笔收款走同一套重算，避免两条路径各写各的。
            // 顺带修掉一处旧偏差：上面的循环会 continue 掉 amount <= 0 的项（不建收款行），
            // 而 $totalPayment 把它们算进去了 —— 原先的 bcadd 会让账单虚增这部分。
            $this->syncInvoicePaidAmount($invoice->id);
            $invoice->refresh();

            // 积分与累计消费已在循环里按笔结算，这里只做一次等级复核
            $this->checkMemberUpgrade($patient);

            DB::commit();

            $diff   = bcsub($totalPayment, $outstanding, 2);
            $change = bccomp($diff, '0', 2) > 0 ? $diff : '0';

            return [
                'status' => true,
                'message' => __('invoices.payment_recorded_successfully'),
                'paid_amount' => $totalPayment,
                'change_due' => $change,
                'new_balance' => $invoice->outstanding_amount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('processMixedPayment failed', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred')];
        }
    }

    /**
     * Get translated payment methods list.
     */
    public function getPaymentMethodsList(): array
    {
        $methods = [];
        foreach (self::PAYMENT_METHODS as $key => $value) {
            $methods[] = [
                'value' => $key,
                'label' => __($value['label']),
                'fee' => $value['fee'],
            ];
        }
        return $methods;
    }

    /**
     * Calculate change due for a payment.
     */
    public function calculateChange(int $invoiceId, float $receivedAmount): array
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $outstanding = $invoice->outstanding_amount;
        $change = max(0, $receivedAmount - $outstanding);

        return [
            'outstanding' => $outstanding,
            'received' => $receivedAmount,
            'change_due' => $change,
        ];
    }
}
