<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalServicesSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充口腔诊疗服务项目数据
     *
     * @return void
     */
    public function run()
    {
        // 获取第一个管理员用户ID作为_who_added
        $adminId = DB::table('users')->where('role_id', 1)->value('id') ?? 1;

        $services = [
            // ========== 口腔检查与诊断 ==========
            ['name' => '口腔常规检查', 'price' => 50.00, 'category' => '检查诊断'],
            ['name' => '全景X光片', 'price' => 150.00, 'category' => '检查诊断'],
            ['name' => '小牙片', 'price' => 30.00, 'category' => '检查诊断'],
            ['name' => 'CBCT(口腔CT)', 'price' => 500.00, 'category' => '检查诊断'],
            ['name' => '头颅侧位片', 'price' => 200.00, 'category' => '检查诊断'],

            // ========== 洁牙与预防 ==========
            ['name' => '超声波洁牙', 'price' => 200.00, 'category' => '洁牙预防'],
            ['name' => '喷砂洁牙', 'price' => 300.00, 'category' => '洁牙预防'],
            ['name' => '深度洁牙(龈下刮治)', 'price' => 400.00, 'category' => '洁牙预防'],
            ['name' => '牙齿抛光', 'price' => 100.00, 'category' => '洁牙预防'],
            ['name' => '氟化物涂布', 'price' => 150.00, 'category' => '洁牙预防'],
            ['name' => '窝沟封闭(每颗)', 'price' => 100.00, 'category' => '洁牙预防'],

            // ========== 补牙(充填治疗) ==========
            ['name' => '树脂补牙(小)', 'price' => 200.00, 'category' => '补牙充填'],
            ['name' => '树脂补牙(中)', 'price' => 300.00, 'category' => '补牙充填'],
            ['name' => '树脂补牙(大)', 'price' => 400.00, 'category' => '补牙充填'],
            ['name' => '玻璃离子充填', 'price' => 150.00, 'category' => '补牙充填'],
            ['name' => '嵌体修复', 'price' => 1500.00, 'category' => '补牙充填'],
            ['name' => '牙齿贴面', 'price' => 2500.00, 'category' => '补牙充填'],

            // ========== 根管治疗 ==========
            ['name' => '前牙根管治疗', 'price' => 800.00, 'category' => '根管治疗'],
            ['name' => '前磨牙根管治疗', 'price' => 1200.00, 'category' => '根管治疗'],
            ['name' => '磨牙根管治疗', 'price' => 1800.00, 'category' => '根管治疗'],
            ['name' => '根管再治疗', 'price' => 2500.00, 'category' => '根管治疗'],
            ['name' => '根尖手术', 'price' => 2000.00, 'category' => '根管治疗'],
            ['name' => '活髓保存治疗', 'price' => 500.00, 'category' => '根管治疗'],

            // ========== 拔牙 ==========
            ['name' => '乳牙拔除', 'price' => 100.00, 'category' => '拔牙'],
            ['name' => '前牙拔除', 'price' => 200.00, 'category' => '拔牙'],
            ['name' => '前磨牙拔除', 'price' => 300.00, 'category' => '拔牙'],
            ['name' => '磨牙拔除', 'price' => 400.00, 'category' => '拔牙'],
            ['name' => '智齿拔除(简单)', 'price' => 500.00, 'category' => '拔牙'],
            ['name' => '智齿拔除(复杂)', 'price' => 1000.00, 'category' => '拔牙'],
            ['name' => '阻生牙拔除', 'price' => 1500.00, 'category' => '拔牙'],
            ['name' => '残根残冠拔除', 'price' => 300.00, 'category' => '拔牙'],

            // ========== 牙周治疗 ==========
            ['name' => '牙周基础治疗', 'price' => 500.00, 'category' => '牙周治疗'],
            ['name' => '牙周翻瓣术', 'price' => 2000.00, 'category' => '牙周治疗'],
            ['name' => '牙龈切除术', 'price' => 800.00, 'category' => '牙周治疗'],
            ['name' => '牙周夹板固定', 'price' => 600.00, 'category' => '牙周治疗'],
            ['name' => '牙周维护治疗', 'price' => 300.00, 'category' => '牙周治疗'],

            // ========== 修复(假牙) ==========
            ['name' => '全瓷冠', 'price' => 3000.00, 'category' => '修复'],
            ['name' => '烤瓷冠(贵金属)', 'price' => 2500.00, 'category' => '修复'],
            ['name' => '烤瓷冠(普通)', 'price' => 1500.00, 'category' => '修复'],
            ['name' => '临时牙冠', 'price' => 200.00, 'category' => '修复'],
            ['name' => '活动义齿(每颗)', 'price' => 300.00, 'category' => '修复'],
            ['name' => '全口义齿', 'price' => 3000.00, 'category' => '修复'],
            ['name' => '吸附性义齿', 'price' => 8000.00, 'category' => '修复'],
            ['name' => '固定桥(每单位)', 'price' => 2000.00, 'category' => '修复'],

            // ========== 种植牙 ==========
            ['name' => '种植体植入(国产)', 'price' => 6000.00, 'category' => '种植'],
            ['name' => '种植体植入(进口标准)', 'price' => 10000.00, 'category' => '种植'],
            ['name' => '种植体植入(进口高端)', 'price' => 15000.00, 'category' => '种植'],
            ['name' => '种植牙冠修复', 'price' => 4000.00, 'category' => '种植'],
            ['name' => '骨粉植入', 'price' => 2000.00, 'category' => '种植'],
            ['name' => '骨膜覆盖', 'price' => 1500.00, 'category' => '种植'],
            ['name' => '上颌窦提升术', 'price' => 5000.00, 'category' => '种植'],
            ['name' => 'All-on-4种植', 'price' => 80000.00, 'category' => '种植'],

            // ========== 正畸(矫正) ==========
            ['name' => '正畸检查与方案设计', 'price' => 500.00, 'category' => '正畸'],
            ['name' => '金属托槽矫正', 'price' => 15000.00, 'category' => '正畸'],
            ['name' => '陶瓷托槽矫正', 'price' => 22000.00, 'category' => '正畸'],
            ['name' => '自锁托槽矫正', 'price' => 25000.00, 'category' => '正畸'],
            ['name' => '隐形矫正(国产)', 'price' => 25000.00, 'category' => '正畸'],
            ['name' => '隐形矫正(进口)', 'price' => 45000.00, 'category' => '正畸'],
            ['name' => '舌侧矫正', 'price' => 50000.00, 'category' => '正畸'],
            ['name' => '正畸复诊(调整)', 'price' => 300.00, 'category' => '正畸'],
            ['name' => '保持器', 'price' => 800.00, 'category' => '正畸'],

            // ========== 儿童口腔 ==========
            ['name' => '儿童涂氟', 'price' => 100.00, 'category' => '儿童口腔'],
            ['name' => '儿童窝沟封闭', 'price' => 80.00, 'category' => '儿童口腔'],
            ['name' => '儿童补牙', 'price' => 150.00, 'category' => '儿童口腔'],
            ['name' => '儿童根管治疗', 'price' => 400.00, 'category' => '儿童口腔'],
            ['name' => '儿童预成冠', 'price' => 500.00, 'category' => '儿童口腔'],
            ['name' => '间隙保持器', 'price' => 600.00, 'category' => '儿童口腔'],
            ['name' => '儿童早期矫正', 'price' => 8000.00, 'category' => '儿童口腔'],

            // ========== 美容牙科 ==========
            ['name' => '冷光美白', 'price' => 2000.00, 'category' => '美容牙科'],
            ['name' => '家用美白套装', 'price' => 800.00, 'category' => '美容牙科'],
            ['name' => '瓷贴面(每颗)', 'price' => 3000.00, 'category' => '美容牙科'],
            ['name' => '树脂贴面(每颗)', 'price' => 800.00, 'category' => '美容牙科'],
            ['name' => '牙龈美学修整', 'price' => 1000.00, 'category' => '美容牙科'],

            // ========== 口腔外科 ==========
            ['name' => '口腔囊肿摘除', 'price' => 2000.00, 'category' => '口腔外科'],
            ['name' => '系带修整术', 'price' => 500.00, 'category' => '口腔外科'],
            ['name' => '颌骨囊肿手术', 'price' => 5000.00, 'category' => '口腔外科'],
            ['name' => '颞下颌关节治疗', 'price' => 1000.00, 'category' => '口腔外科'],
            ['name' => '牙槽骨修整术', 'price' => 800.00, 'category' => '口腔外科'],

            // ========== 其他 ==========
            ['name' => '局部麻醉', 'price' => 50.00, 'category' => '其他'],
            ['name' => '全麻/镇静', 'price' => 2000.00, 'category' => '其他'],
            ['name' => '急诊处理', 'price' => 100.00, 'category' => '其他'],
            ['name' => '开髓引流', 'price' => 150.00, 'category' => '其他'],
            ['name' => '复诊费', 'price' => 30.00, 'category' => '其他'],
        ];

        $id = 1;
        foreach ($services as $service) {
            DB::table('medical_services')->insert([
                'id' => $id++,
                'name' => $service['name'],
                'price' => $service['price'],
                'category' => $service['category'],
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("已创建 " . count($services) . " 个口腔诊疗服务项目");
        $this->command->info("服务分类：检查诊断、洁牙预防、补牙充填、根管治疗、拔牙、牙周治疗、修复、种植、正畸、儿童口腔、美容牙科、口腔外科、其他");
    }
}
