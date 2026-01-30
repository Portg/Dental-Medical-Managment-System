<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategoriesSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充费用分类数据（口腔门诊常用）
     *
     * @return void
     */
    public function run()
    {
        // 获取第一个管理员用户ID作为_who_added
        $adminId = DB::table('users')->where('role_id', 1)->value('id') ?? 1;

        $categories = [
            // ========== 人力成本 ==========
            ['name' => '工资薪酬', 'description' => '员工基本工资及岗位津贴'],
            ['name' => '社保公积金', 'description' => '社会保险及住房公积金'],
            ['name' => '绩效奖金', 'description' => '员工绩效考核奖金'],
            ['name' => '培训费用', 'description' => '员工技能培训及继续教育费用'],
            ['name' => '招聘费用', 'description' => '人员招聘相关费用'],
            ['name' => '员工福利', 'description' => '员工节日福利、体检等'],

            // ========== 房租物业 ==========
            ['name' => '房租', 'description' => '诊所场地租赁费'],
            ['name' => '物业费', 'description' => '物业管理费'],
            ['name' => '装修维护', 'description' => '场地装修及维护费用'],

            // ========== 水电能耗 ==========
            ['name' => '电费', 'description' => '电力费用'],
            ['name' => '水费', 'description' => '自来水费用'],
            ['name' => '燃气费', 'description' => '燃气费用'],
            ['name' => '暖气/空调费', 'description' => '取暖或制冷费用'],

            // ========== 医疗耗材 ==========
            ['name' => '一次性耗材', 'description' => '手套、口罩、注射器等一次性用品'],
            ['name' => '口腔材料', 'description' => '树脂、印模材料、粘接剂等'],
            ['name' => '种植材料', 'description' => '种植体、骨粉、骨膜等'],
            ['name' => '正畸材料', 'description' => '托槽、弓丝、隐形牙套等'],
            ['name' => '修复材料', 'description' => '烤瓷材料、全瓷材料等'],
            ['name' => '药品', 'description' => '麻药、消炎药、漱口水等'],
            ['name' => '消毒用品', 'description' => '消毒液、灭菌袋等消毒用品'],

            // ========== 设备资产 ==========
            ['name' => '设备采购', 'description' => '医疗设备购置'],
            ['name' => '设备维修', 'description' => '设备维护保养费'],
            ['name' => '器械采购', 'description' => '器械工具购置'],
            ['name' => '办公设备', 'description' => '电脑、打印机等办公设备'],
            ['name' => '办公用品', 'description' => '纸张、文具等日常办公用品'],
            ['name' => '家具购置', 'description' => '桌椅、柜子等家具'],

            // ========== 运营费用 ==========
            ['name' => '网络通讯', 'description' => '网络、电话等通讯费'],
            ['name' => '软件服务', 'description' => '管理软件、会员系统等IT费用'],
            ['name' => '保险费用', 'description' => '财产保险、医疗责任险等'],
            ['name' => '税费', 'description' => '各类税费支出'],
            ['name' => '银行手续费', 'description' => '银行转账、POS机等手续费'],
            ['name' => '快递物流', 'description' => '快递及物流费用'],

            // ========== 市场营销 ==========
            ['name' => '广告宣传', 'description' => '广告投放、宣传制作费'],
            ['name' => '平台推广', 'description' => '美团、抖音等平台推广费'],
            ['name' => '活动费用', 'description' => '促销活动、义诊等费用'],
            ['name' => '礼品采购', 'description' => '客户礼品、伴手礼等'],

            // ========== 行政管理 ==========
            ['name' => '差旅费', 'description' => '出差交通、住宿费'],
            ['name' => '餐饮招待', 'description' => '工作餐及业务招待费'],
            ['name' => '车辆费用', 'description' => '车辆油费、保养、停车费等'],
            ['name' => '会议费', 'description' => '会议及活动场地费用'],

            // ========== 技工加工 ==========
            ['name' => '义齿加工', 'description' => '假牙、牙冠等技工加工费'],
            ['name' => '正畸加工', 'description' => '隐形牙套、保持器等加工费'],

            // ========== 其他 ==========
            ['name' => '法律服务', 'description' => '法律咨询及服务费'],
            ['name' => '审计服务', 'description' => '财务审计服务费'],
            ['name' => '杂项支出', 'description' => '其他零星支出'],
        ];

        $id = 1;
        foreach ($categories as $category) {
            DB::table('expense_categories')->insert([
                'id' => $id++,
                'name' => $category['name'],
                'description' => $category['description'] ?? null,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("已创建 " . count($categories) . " 个费用分类");
        $this->command->info("分类涵盖：人力成本、房租物业、水电能耗、医疗耗材、设备资产、运营费用、市场营销、行政管理、技工加工、其他");
    }
}
