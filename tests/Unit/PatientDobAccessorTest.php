<?php

namespace Tests\Unit;

use App\Patient;
use Tests\TestCase;

/**
 * patients 表没有 dob 列，dob 是 date_of_birth 的只读派生属性。
 * 前端多处（病历编辑 select2、预约抽屉、今日工作抽屉）直接消费模型 payload
 * 里的 dob 算年龄，所以它必须出现在 toArray() 里。
 */
class PatientDobAccessorTest extends TestCase
{
    public function test_dob_derives_from_date_of_birth(): void
    {
        $patient = new Patient(['date_of_birth' => '1990-05-15']);

        $this->assertSame('1990-05-15', $patient->dob);
    }

    public function test_dob_is_null_when_date_of_birth_missing(): void
    {
        $this->assertNull((new Patient())->dob);
    }

    public function test_dob_is_appended_to_array_serialization(): void
    {
        $patient = new Patient(['date_of_birth' => '1990-05-15']);

        $array = $patient->toArray();

        $this->assertArrayHasKey('dob', $array);
        $this->assertSame('1990-05-15', $array['dob']);
    }

    public function test_dob_is_not_mass_assignable(): void
    {
        // 写入必须走 date_of_birth；dob 进 $fillable 会试图写一个不存在的列
        $patient = new Patient(['dob' => '1990-05-15']);

        $this->assertNull($patient->getAttributes()['dob'] ?? null);
        $this->assertNull($patient->dob);
    }
}
