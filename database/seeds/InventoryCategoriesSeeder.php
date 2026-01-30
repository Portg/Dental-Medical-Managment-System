<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first user as the creator
        $userId = DB::table('users')->first()->id ?? 1;

        $categories = [
            // Drugs (药品)
            [
                'name' => '口腔药品',
                'code' => 'DRUG-ORAL',
                'type' => 'drug',
                'description' => '口腔治疗相关药品',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '麻醉药品',
                'code' => 'DRUG-ANES',
                'type' => 'drug',
                'description' => '麻醉及镇痛类药品',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => '抗生素',
                'code' => 'DRUG-ANTI',
                'type' => 'drug',
                'description' => '抗生素类药品',
                'sort_order' => 3,
                'is_active' => true,
            ],

            // Consumables (耗材)
            [
                'name' => '一次性耗材',
                'code' => 'CONS-DISP',
                'type' => 'consumable',
                'description' => '一次性使用耗材',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => '充填材料',
                'code' => 'CONS-FILL',
                'type' => 'consumable',
                'description' => '牙齿充填材料',
                'sort_order' => 11,
                'is_active' => true,
            ],
            [
                'name' => '根管材料',
                'code' => 'CONS-ROOT',
                'type' => 'consumable',
                'description' => '根管治疗材料',
                'sort_order' => 12,
                'is_active' => true,
            ],
            [
                'name' => '粘接材料',
                'code' => 'CONS-BOND',
                'type' => 'consumable',
                'description' => '粘接及固位材料',
                'sort_order' => 13,
                'is_active' => true,
            ],
            [
                'name' => '印模材料',
                'code' => 'CONS-IMPR',
                'type' => 'consumable',
                'description' => '印模及取模材料',
                'sort_order' => 14,
                'is_active' => true,
            ],

            // Instruments (器械)
            [
                'name' => '手持器械',
                'code' => 'INST-HAND',
                'type' => 'instrument',
                'description' => '手持诊疗器械',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => '车针器械',
                'code' => 'INST-BUR',
                'type' => 'instrument',
                'description' => '车针及钻头',
                'sort_order' => 21,
                'is_active' => true,
            ],
            [
                'name' => '正畸器械',
                'code' => 'INST-ORTH',
                'type' => 'instrument',
                'description' => '正畸治疗器械',
                'sort_order' => 22,
                'is_active' => true,
            ],

            // Dental Materials (义齿材料)
            [
                'name' => '金属材料',
                'code' => 'DENT-META',
                'type' => 'dental_material',
                'description' => '义齿金属材料',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => '瓷材料',
                'code' => 'DENT-CERA',
                'type' => 'dental_material',
                'description' => '陶瓷及瓷材料',
                'sort_order' => 31,
                'is_active' => true,
            ],
            [
                'name' => '树脂材料',
                'code' => 'DENT-RESI',
                'type' => 'dental_material',
                'description' => '树脂类材料',
                'sort_order' => 32,
                'is_active' => true,
            ],

            // Office Supplies (办公用品)
            [
                'name' => '办公文具',
                'code' => 'OFFI-STAT',
                'type' => 'office',
                'description' => '办公文具用品',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => '清洁用品',
                'code' => 'OFFI-CLEA',
                'type' => 'office',
                'description' => '清洁及消毒用品',
                'sort_order' => 41,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            // Check if category already exists
            $exists = DB::table('inventory_categories')
                ->where('code', $category['code'])
                ->exists();

            if (!$exists) {
                DB::table('inventory_categories')->insert(array_merge($category, [
                    '_who_added' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $this->command->info('Inventory categories seeded successfully!');
    }
}
