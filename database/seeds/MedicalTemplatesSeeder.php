<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get a user ID for _who_added (use first user or ID 1)
        $userId = DB::table('users')->first()->id ?? 1;

        // =====================================================================
        // Chief Complaint Templates (主诉模板)
        // =====================================================================
        $chiefComplaintTemplates = [
            [
                'name' => '洁牙主诉',
                'code' => 'zs-jy',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '患者要求洁牙，自觉牙龈出血、口腔异味',
                'description' => '洁牙主诉模板',
            ],
            [
                'name' => '补牙主诉',
                'code' => 'zs-by',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '患者主诉__牙有洞，进食嵌塞/冷热刺激敏感__天',
                'description' => '补牙主诉模板',
            ],
            [
                'name' => '拔牙主诉',
                'code' => 'zs-ba',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '患者要求拔除__牙，该牙反复疼痛/松动/无法保留',
                'description' => '拔牙主诉模板',
            ],
            [
                'name' => '牙痛主诉',
                'code' => 'zs-yt',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '患者主诉__牙自发痛/夜间痛/咬合痛，持续__天',
                'description' => '牙痛主诉模板',
            ],
            [
                'name' => '牙龈出血主诉',
                'code' => 'zs-yycx',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '患者主诉牙龈出血__天，刷牙时加重',
                'description' => '牙龈出血主诉模板',
            ],
            [
                'name' => '牙齿松动主诉',
                'code' => 'zs-ycsd',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '患者主诉__牙松动__天，咀嚼无力',
                'description' => '牙齿松动主诉模板',
            ],
            [
                'name' => '正畸复诊主诉',
                'code' => 'zs-zjfz',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '正畸复诊，无明显不适',
                'description' => '正畸复诊主诉模板',
            ],
            [
                'name' => '种植复诊主诉',
                'code' => 'zs-zzfz',
                'category' => 'system',
                'type' => 'chief_complaint',
                'content' => '种植术后__天复诊，无明显不适',
                'description' => '种植复诊主诉模板',
            ],
        ];

        // =====================================================================
        // Diagnosis Templates (诊断模板)
        // =====================================================================
        $diagnosisTemplates = [
            [
                'name' => '龋齿诊断',
                'code' => 'zd-qc',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '__牙浅龋/中龋/深龋',
                'description' => '龋齿诊断模板',
            ],
            [
                'name' => '牙髓炎诊断',
                'code' => 'zd-ysy',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '__牙急性/慢性牙髓炎',
                'description' => '牙髓炎诊断模板',
            ],
            [
                'name' => '根尖周炎诊断',
                'code' => 'zd-gjzy',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '__牙急性/慢性根尖周炎',
                'description' => '根尖周炎诊断模板',
            ],
            [
                'name' => '牙龈炎诊断',
                'code' => 'zd-yyy',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '慢性牙龈炎',
                'description' => '牙龈炎诊断模板',
            ],
            [
                'name' => '牙周炎诊断',
                'code' => 'zd-yzy',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '慢性牙周炎（轻度/中度/重度）',
                'description' => '牙周炎诊断模板',
            ],
            [
                'name' => '残根残冠诊断',
                'code' => 'zd-cgcg',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '__牙残根/残冠',
                'description' => '残根残冠诊断模板',
            ],
            [
                'name' => '牙齿缺失诊断',
                'code' => 'zd-ycqs',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '__牙缺失',
                'description' => '牙齿缺失诊断模板',
            ],
            [
                'name' => '智齿冠周炎诊断',
                'code' => 'zd-zcgzy',
                'category' => 'system',
                'type' => 'diagnosis',
                'content' => '__智齿冠周炎',
                'description' => '智齿冠周炎诊断模板',
            ],
        ];

        // =====================================================================
        // Treatment Plan Templates (治疗模板)
        // =====================================================================
        $treatmentPlanTemplates = [
            [
                'name' => '洁牙治疗',
                'code' => 'zl-jy',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '超声龈上洁治，抛光，冲洗上药',
                'description' => '洁牙治疗模板',
            ],
            [
                'name' => '树脂充填治疗',
                'code' => 'zl-szcf',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '去腐备洞，垫底/直接充填，树脂充填，调牙合抛光',
                'description' => '树脂充填治疗模板',
            ],
            [
                'name' => '拔牙治疗',
                'code' => 'zl-ba',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '局麻下拔除__牙，拔牙窝搔刮，明胶海绵填塞，咬纱布压迫止血',
                'description' => '拔牙治疗模板',
            ],
            [
                'name' => '根管治疗-开髓',
                'code' => 'zl-ggks',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '局麻下开髓，拔髓，根管预备，根管冲洗，根管内封药',
                'description' => '根管治疗开髓模板',
            ],
            [
                'name' => '根管治疗-充填',
                'code' => 'zl-ggcf',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '去除封药，根管冲洗，干燥，根管充填，X线片示根充恰填',
                'description' => '根管充填治疗模板',
            ],
            [
                'name' => '牙周治疗',
                'code' => 'zl-yz',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '龈上洁治，龈下刮治（__象限），根面平整，牙周冲洗上药',
                'description' => '牙周治疗模板',
            ],
            [
                'name' => '冠修复取模',
                'code' => 'zl-gxf',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '__牙牙体预备，排龈，硅橡胶取模，比色，临时冠修复',
                'description' => '冠修复取模模板',
            ],
            [
                'name' => '冠修复粘接',
                'code' => 'zl-gzj',
                'category' => 'system',
                'type' => 'treatment_plan',
                'content' => '去除临时冠，试戴全瓷冠/烤瓷冠，调牙合，玻璃离子/树脂粘接',
                'description' => '冠修复粘接模板',
            ],
        ];

        // =====================================================================
        // Progress Note Templates (完整SOAP病历模板)
        // =====================================================================
        $progressNoteTemplates = [
            [
                'name' => '洁牙病历',
                'code' => 'soap-jy',
                'category' => 'system',
                'type' => 'progress_note',
                'content' => json_encode([
                    'subjective' => '患者来诊要求洁牙，无明显不适',
                    'objective' => '全口牙石中度，牙龈轻度红肿，探诊出血',
                    'assessment' => '慢性牙龈炎，牙石',
                    'plan' => '超声波洁治，抛光，口腔卫生指导'
                ]),
                'description' => '常规洁牙完整病历模板',
            ],
            [
                'name' => '补牙病历',
                'code' => 'soap-by',
                'category' => 'system',
                'type' => 'progress_note',
                'content' => json_encode([
                    'subjective' => '患者主诉：__牙疼痛/酸痛，__天',
                    'objective' => '__牙可见龋洞，探诊敏感，叩诊(-)，冷诊敏感',
                    'assessment' => '__牙浅龋/中龋/深龋',
                    'plan' => '去腐备洞，树脂充填修复'
                ]),
                'description' => '树脂补牙完整病历模板',
            ],
            [
                'name' => '拔牙病历',
                'code' => 'soap-ba',
                'category' => 'system',
                'type' => 'progress_note',
                'content' => json_encode([
                    'subjective' => '患者要求拔除__牙，因__',
                    'objective' => '__牙残冠/残根/松动III度，叩诊(+)',
                    'assessment' => '__牙无保留价值',
                    'plan' => '局麻下拔除__牙，医嘱'
                ]),
                'description' => '拔牙完整病历模板',
            ],
            [
                'name' => '根管治疗病历',
                'code' => 'soap-gg',
                'category' => 'system',
                'type' => 'progress_note',
                'content' => json_encode([
                    'subjective' => '患者主诉：__牙自发痛/夜间痛/__天',
                    'objective' => '__牙可见龋洞/充填物，叩诊(+)，冷诊无反应/剧烈疼痛',
                    'assessment' => '__牙急性/慢性牙髓炎/根尖周炎',
                    'plan' => '局麻开髓，根管预备，封药，择期完成根管治疗'
                ]),
                'description' => '根管治疗完整病历模板',
            ],
            [
                'name' => '牙周治疗病历',
                'code' => 'soap-yz',
                'category' => 'system',
                'type' => 'progress_note',
                'content' => json_encode([
                    'subjective' => '患者主诉牙龈出血/肿痛/口臭',
                    'objective' => '牙龈红肿，探诊深度__mm，探诊出血，牙石++',
                    'assessment' => '慢性牙周炎',
                    'plan' => '龈上洁治+龈下刮治，口腔卫生指导'
                ]),
                'description' => '牙周治疗完整病历模板',
            ],
        ];

        // Merge all templates
        $templates = array_merge(
            $chiefComplaintTemplates,
            $diagnosisTemplates,
            $treatmentPlanTemplates,
            $progressNoteTemplates
        );

        foreach ($templates as $template) {
            DB::table('medical_templates')->insert([
                'name' => $template['name'],
                'code' => $template['code'],
                'category' => $template['category'],
                'type' => $template['type'],
                'content' => $template['content'],
                'description' => $template['description'],
                'is_active' => true,
                'usage_count' => 0,
                'created_by' => $userId,
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create quick phrases
        $phrases = [
            // Examination phrases
            ['shortcut' => 'tzy+', 'phrase' => '探诊(+)', 'category' => 'examination'],
            ['shortcut' => 'tzy-', 'phrase' => '探诊(-)', 'category' => 'examination'],
            ['shortcut' => 'kzy+', 'phrase' => '叩诊(+)', 'category' => 'examination'],
            ['shortcut' => 'kzy-', 'phrase' => '叩诊(-)', 'category' => 'examination'],
            ['shortcut' => 'lrzhen', 'phrase' => '冷热诊敏感', 'category' => 'examination'],
            ['shortcut' => 'lrzy-', 'phrase' => '冷热诊无反应', 'category' => 'examination'],
            ['shortcut' => 'yyzs', 'phrase' => '牙龈红肿', 'category' => 'examination'],
            ['shortcut' => 'tzsx', 'phrase' => '探诊出血', 'category' => 'examination'],
            ['shortcut' => 'sdd1', 'phrase' => '松动度I度', 'category' => 'examination'],
            ['shortcut' => 'sdd2', 'phrase' => '松动度II度', 'category' => 'examination'],
            ['shortcut' => 'sdd3', 'phrase' => '松动度III度', 'category' => 'examination'],
            ['shortcut' => 'ysz++', 'phrase' => '牙石++', 'category' => 'examination'],
            ['shortcut' => 'qdhs', 'phrase' => '浅龋洞', 'category' => 'examination'],
            ['shortcut' => 'zdhs', 'phrase' => '中龋洞', 'category' => 'examination'],
            ['shortcut' => 'sdhs', 'phrase' => '深龋洞', 'category' => 'examination'],

            // Diagnosis phrases
            ['shortcut' => 'jxyy', 'phrase' => '急性牙髓炎', 'category' => 'diagnosis'],
            ['shortcut' => 'mxyy', 'phrase' => '慢性牙髓炎', 'category' => 'diagnosis'],
            ['shortcut' => 'gjzy', 'phrase' => '根尖周炎', 'category' => 'diagnosis'],
            ['shortcut' => 'yyy', 'phrase' => '牙龈炎', 'category' => 'diagnosis'],
            ['shortcut' => 'yzy', 'phrase' => '牙周炎', 'category' => 'diagnosis'],
            ['shortcut' => 'qq', 'phrase' => '浅龋', 'category' => 'diagnosis'],
            ['shortcut' => 'zq', 'phrase' => '中龋', 'category' => 'diagnosis'],
            ['shortcut' => 'sq', 'phrase' => '深龋', 'category' => 'diagnosis'],

            // Treatment phrases
            ['shortcut' => 'csbj', 'phrase' => '超声波洁治', 'category' => 'treatment'],
            ['shortcut' => 'szcf', 'phrase' => '树脂充填', 'category' => 'treatment'],
            ['shortcut' => 'ggzl', 'phrase' => '根管治疗', 'category' => 'treatment'],
            ['shortcut' => 'jm', 'phrase' => '局麻', 'category' => 'treatment'],
            ['shortcut' => 'bya', 'phrase' => '拔牙', 'category' => 'treatment'],
            ['shortcut' => 'pg', 'phrase' => '抛光', 'category' => 'treatment'],
            ['shortcut' => 'yzhy', 'phrase' => '印模取模', 'category' => 'treatment'],
        ];

        foreach ($phrases as $phrase) {
            DB::table('quick_phrases')->insert([
                'shortcut' => $phrase['shortcut'],
                'phrase' => $phrase['phrase'],
                'category' => $phrase['category'],
                'scope' => 'system',
                'is_active' => true,
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
