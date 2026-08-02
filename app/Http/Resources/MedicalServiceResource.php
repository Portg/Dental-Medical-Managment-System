<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalServiceResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'price'      => $this->price,
            // medical_services 有真实的 is_active 列（2026_01_18_225200 迁移），
            // 用它而不是拿软删除状态冒充；两者语义不同：停用 ≠ 删除
            'is_active'  => (bool) $this->is_active,
            'created_at' => $this->dateTime($this->created_at),
            'updated_at' => $this->dateTime($this->updated_at),
        ];
    }
}
