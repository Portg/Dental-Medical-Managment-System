<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'patient_no'        => $this->patient_no,
            'full_name'         => $this->full_name,
            'phone_no'          => $this->phone_no,
            'member_no'         => $this->member_no,
            'member_level_id'   => $this->member_level_id,
            'member_level'      => $this->whenLoaded('memberLevel', fn () => [
                'id'            => $this->memberLevel->id,
                'name'          => $this->memberLevel->name,
                'code'          => $this->memberLevel->code,
                'discount_rate' => $this->memberLevel->discount_rate,
                'points_rate'   => $this->memberLevel->points_rate,
            ]),
            'member_balance'    => $this->member_balance,
            'member_points'     => $this->member_points,
            'total_consumption' => $this->total_consumption,
            'member_status'     => $this->member_status,
            'member_since'      => $this->dateOnly($this->member_since),
            'member_expiry'     => $this->dateOnly($this->member_expiry),
            'created_at'        => $this->dateTime($this->created_at),
        ];
    }
}
