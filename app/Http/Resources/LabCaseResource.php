<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabCaseResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        $items = $this->items ?? collect();
        $first = $items->first();

        return [
            'id'                    => $this->id,
            'lab_case_no'           => $this->lab_case_no,
            'processing_days'       => $this->processing_days,

            // 明细。2026_03_06 迁移后修复体信息挂在 lab_case_items 上，
            // 一张单最多 4 件；下面的平铺字段取第一件，兼容旧调用方。
            'items'                 => $items->map(fn ($item) => [
                'id'              => $item->id,
                'prosthesis_type' => $item->prosthesis_type,
                'material'        => $item->material,
                'color_shade'     => $item->color_shade,
                'teeth_positions' => $item->teeth_positions,
                'qty'             => $item->qty,
                'sort_order'      => $item->sort_order,
            ])->values(),
            'prosthesis_type'       => $first->prosthesis_type ?? null,
            'material'              => $first->material ?? null,
            'color_shade'           => $first->color_shade ?? null,
            'teeth_positions'       => $first->teeth_positions ?? null,

            'special_requirements'  => $this->special_requirements,
            'status'                => $this->status,
            'sent_date'             => $this->dateOnly($this->sent_date),
            'expected_return_date'  => $this->dateOnly($this->expected_return_date),
            'actual_return_date'    => $this->dateOnly($this->actual_return_date),
            'lab_fee'               => (float) $this->lab_fee,
            'patient_charge'        => (float) $this->patient_charge,
            'profit'                => $this->profit,
            'is_overdue'            => $this->is_overdue,
            'quality_rating'        => $this->quality_rating,
            'rework_count'          => $this->rework_count,
            'rework_reason'         => $this->rework_reason,
            'notes'                 => $this->notes,
            'patient_id'            => $this->patient_id,
            'patient'               => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'patient_no' => $this->patient->patient_no,
                'full_name' => $this->patient->surname . $this->patient->othername,
            ]),
            'doctor_id'             => $this->doctor_id,
            'doctor'                => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'full_name' => $this->doctor->full_name ?? $this->doctor->othername,
            ]),
            'lab_id'                => $this->lab_id,
            'lab'                   => $this->whenLoaded('lab', fn () => [
                'id'   => $this->lab->id,
                'name' => $this->lab->name,
            ]),
            'appointment_id'        => $this->appointment_id,
            'medical_case_id'       => $this->medical_case_id,
            'created_at'            => $this->dateTime($this->created_at),
            'updated_at'            => $this->dateTime($this->updated_at),
        ];
    }
}
