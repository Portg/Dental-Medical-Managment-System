<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'code'            => $this->code,
            'color'           => $this->color,
            'discount_rate'   => $this->discount_rate,
            'min_consumption' => $this->min_consumption,
            'points_rate'     => $this->points_rate,
            'benefits'        => $this->benefits,
            'sort_order'      => $this->sort_order,
            'is_default'      => $this->is_default,
            'is_active'       => $this->is_active,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
