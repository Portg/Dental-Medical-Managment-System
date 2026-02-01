<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 牙科诊所会计科目表（三层结构）：
     * 会计要素(accounting_equations) → 科目分类(chart_of_account_categories) → 具体科目(chart_of_account_items)
     *
     * @return void
     */
    public function run()
    {
        // 清空已有数据，确保可重复执行（禁用外键检查以避免约束冲突）
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('chart_of_account_items')->truncate();
        DB::table('chart_of_account_categories')->truncate();
        DB::table('accounting_equations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 获取审计用户
        $userId = DB::table('users')->first()->id ?? 1;

        $this->command->info('Seeding chart of accounts...');

        // ========================================
        // 1. 会计要素 (Accounting Equations)
        // ========================================
        $equations = [
            ['name' => '资产',       'sort_by' => 1, 'active_tab' => 'yes'],
            ['name' => '负债',       'sort_by' => 2, 'active_tab' => 'no'],
            ['name' => '所有者权益', 'sort_by' => 3, 'active_tab' => 'no'],
            ['name' => '收入',       'sort_by' => 4, 'active_tab' => 'no'],
            ['name' => '费用',       'sort_by' => 5, 'active_tab' => 'no'],
        ];

        $equationIds = [];
        foreach ($equations as $eq) {
            $equationIds[$eq['name']] = DB::table('accounting_equations')->insertGetId([
                'name'       => $eq['name'],
                'sort_by'    => $eq['sort_by'],
                'active_tab' => $eq['active_tab'],
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ========================================
        // 2. 科目分类 + 具体科目
        // ========================================
        $structure = [
            // --- 资产类 ---
            '资产' => [
                '流动资产' => [
                    ['name' => '库存现金',     'description' => '诊所日常备用现金'],
                    ['name' => '银行存款',     'description' => '诊所银行账户存款'],
                    ['name' => '微信收款',     'description' => '微信支付收款账户'],
                    ['name' => '支付宝收款',   'description' => '支付宝收款账户'],
                    ['name' => '应收账款',     'description' => '患者未结算的诊疗费用'],
                    ['name' => '应收保险款',   'description' => '保险公司待结算款项'],
                    ['name' => '预付账款',     'description' => '预付给供应商的材料款'],
                    ['name' => '其他应收款',   'description' => '员工借款、押金等'],
                ],
                '库存资产' => [
                    ['name' => '医疗耗材',     'description' => '一次性手套、棉球、消毒材料等'],
                    ['name' => '口腔材料',     'description' => '树脂、印模材料、牙冠材料等'],
                    ['name' => '药品库存',     'description' => '麻药、消炎药、止痛药等'],
                    ['name' => '种植体库存',   'description' => '各品牌种植体及配件'],
                    ['name' => '正畸材料',     'description' => '托槽、弓丝、隐形矫治器等'],
                ],
                '固定资产' => [
                    ['name' => '牙椅设备',     'description' => '综合治疗台、牙椅'],
                    ['name' => '影像设备',     'description' => 'CT、全景机、口内扫描仪'],
                    ['name' => '消毒设备',     'description' => '高温灭菌器、超声波清洗机'],
                    ['name' => '手术器械',     'description' => '种植工具、拔牙器械套装'],
                    ['name' => '办公设备',     'description' => '电脑、打印机、服务器等'],
                    ['name' => '装修及设施',   'description' => '诊室装修、空调、家具等'],
                ],
            ],
            // --- 负债类 ---
            '负债' => [
                '流动负债' => [
                    ['name' => '应付账款',     'description' => '应付供应商的材料款'],
                    ['name' => '预收账款',     'description' => '患者预付费/储值卡余额'],
                    ['name' => '应付工资',     'description' => '应付未发的员工工资'],
                    ['name' => '应交税费',     'description' => '应缴增值税、个税等'],
                    ['name' => '应付社保',     'description' => '应缴社保和公积金'],
                    ['name' => '其他应付款',   'description' => '押金、暂收款等'],
                ],
                '长期负债' => [
                    ['name' => '长期借款',     'description' => '银行贷款等长期借款'],
                    ['name' => '设备分期款',   'description' => '大型设备分期付款余额'],
                ],
            ],
            // --- 所有者权益类 ---
            '所有者权益' => [
                '实收资本' => [
                    ['name' => '实收资本',     'description' => '股东投入的注册资本'],
                    ['name' => '资本公积',     'description' => '股东超额投入等'],
                ],
                '留存收益' => [
                    ['name' => '盈余公积',     'description' => '按规定提取的盈余'],
                    ['name' => '未分配利润',   'description' => '累计未分配的净利润'],
                ],
            ],
            // --- 收入类 ---
            '收入' => [
                '诊疗收入' => [
                    ['name' => '口腔检查收入',   'description' => '初诊检查、复查等'],
                    ['name' => '洁牙收入',       'description' => '超声波洁牙、喷砂洁牙'],
                    ['name' => '补牙收入',       'description' => '树脂充填、嵌体修复'],
                    ['name' => '根管治疗收入',   'description' => '根管治疗、根尖手术'],
                    ['name' => '拔牙收入',       'description' => '简单拔牙、阻生齿拔除'],
                    ['name' => '修复收入',       'description' => '烤瓷牙、全瓷冠、活动义齿'],
                    ['name' => '种植收入',       'description' => '种植体植入、种植修复'],
                    ['name' => '正畸收入',       'description' => '传统正畸、隐形矫正'],
                    ['name' => '牙周治疗收入',   'description' => '刮治、牙周手术'],
                    ['name' => '儿牙收入',       'description' => '窝沟封闭、涂氟、乳牙治疗'],
                    ['name' => '美学修复收入',   'description' => '贴面、美白等'],
                ],
                '其他收入' => [
                    ['name' => '保险结算收入',   'description' => '保险公司结算款'],
                    ['name' => '材料销售收入',   'description' => '牙刷、牙膏等零售'],
                    ['name' => '会员卡收入',     'description' => '会员卡充值收入'],
                    ['name' => '利息收入',       'description' => '银行存款利息'],
                    ['name' => '其他营业外收入', 'description' => '政府补贴、赔偿收入等'],
                ],
            ],
            // --- 费用类 ---
            '费用' => [
                '人力成本' => [
                    ['name' => '医生薪资',       'description' => '牙医基本工资及补贴'],
                    ['name' => '护士薪资',       'description' => '护士基本工资及补贴'],
                    ['name' => '前台薪资',       'description' => '前台接待人员工资'],
                    ['name' => '管理人员薪资',   'description' => '行政管理人员工资'],
                    ['name' => '绩效提成',       'description' => '医生诊疗提成、业绩奖金'],
                    ['name' => '社保公积金',     'description' => '单位承担的社保和公积金'],
                    ['name' => '员工福利',       'description' => '体检、节日福利、培训等'],
                ],
                '材料成本' => [
                    ['name' => '口腔材料消耗',   'description' => '树脂、印模材料等耗材'],
                    ['name' => '种植体成本',     'description' => '种植体及配件采购成本'],
                    ['name' => '正畸材料成本',   'description' => '托槽、矫治器等材料'],
                    ['name' => '修复体成本',     'description' => '技工加工费（牙冠、义齿）'],
                    ['name' => '药品消耗',       'description' => '麻药、消炎药等药品'],
                    ['name' => '一次性耗材',     'description' => '手套、口罩、吸唾管等'],
                ],
                '运营费用' => [
                    ['name' => '房租费用',       'description' => '诊所场地租金'],
                    ['name' => '水电费',         'description' => '水费、电费'],
                    ['name' => '物业管理费',     'description' => '物业费、停车位费等'],
                    ['name' => '设备维护费',     'description' => '牙椅保养、设备维修'],
                    ['name' => '消毒灭菌费',     'description' => '消毒液、灭菌耗材等'],
                    ['name' => '医疗废物处理费', 'description' => '医废回收处置费用'],
                    ['name' => '软件服务费',     'description' => '管理系统、HIS系统等'],
                    ['name' => '通讯费',         'description' => '电话、网络、短信费用'],
                    ['name' => '办公用品费',     'description' => '纸张、文具、打印耗材'],
                ],
                '营销费用' => [
                    ['name' => '线上推广费',     'description' => '美团、大众点评、百度推广'],
                    ['name' => '线下推广费',     'description' => '传单、户外广告等'],
                    ['name' => '活动费用',       'description' => '义诊、口腔健康日等活动'],
                    ['name' => '转介绍奖励',     'description' => '老带新推荐奖励'],
                ],
                '行政费用' => [
                    ['name' => '差旅费',         'description' => '出差交通、住宿费'],
                    ['name' => '招待费',         'description' => '业务接待费用'],
                    ['name' => '培训费',         'description' => '员工继续教育、学术会议'],
                    ['name' => '证照年审费',     'description' => '营业执照、医疗许可证等'],
                    ['name' => '保险费用',       'description' => '医疗责任险、财产险'],
                    ['name' => '折旧费',         'description' => '固定资产折旧摊销'],
                    ['name' => '税费',           'description' => '增值税、附加税等'],
                    ['name' => '其他费用',       'description' => '其他未分类的日常开支'],
                ],
            ],
        ];

        $categoryCount = 0;
        $itemCount = 0;

        foreach ($structure as $equationName => $categories) {
            $equationId = $equationIds[$equationName];

            foreach ($categories as $categoryName => $items) {
                // 插入科目分类
                $categoryId = DB::table('chart_of_account_categories')->insertGetId([
                    'name'                  => $categoryName,
                    'description'           => null,
                    'accounting_equation_id' => $equationId,
                    '_who_added'            => $userId,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
                $categoryCount++;

                // 插入具体科目
                foreach ($items as $item) {
                    DB::table('chart_of_account_items')->insert([
                        'name'                         => $item['name'],
                        'description'                  => $item['description'],
                        'chart_of_account_category_id' => $categoryId,
                        '_who_added'                   => $userId,
                        'created_at'                   => now(),
                        'updated_at'                   => now(),
                    ]);
                    $itemCount++;
                }
            }
        }

        $this->command->info("✓ chart of accounts seeded (5 equations, {$categoryCount} categories, {$itemCount} items)");
    }
}
