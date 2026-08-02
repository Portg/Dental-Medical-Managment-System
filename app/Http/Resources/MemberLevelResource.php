<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberLevelResource extends JsonResource
{
    use FormatsDates;

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
            'created_at'      => $this->dateTime($this->created_at),
            'updated_at'      => $this->dateTime($this->updated_at),
        ];
    }
}
