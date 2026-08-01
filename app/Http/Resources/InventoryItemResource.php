<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'item_code'           => $this->item_code,
            'name'                => $this->name,
            'specification'       => $this->specification,
            'unit'                => $this->unit,
            'category_id'         => $this->category_id,
            'category'            => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'brand'               => $this->brand,
            'reference_price'     => $this->reference_price,
            'selling_price'       => $this->selling_price,
            'current_stock'       => $this->current_stock,
            'average_cost'        => $this->average_cost,
            'track_expiry'        => $this->track_expiry,
            'stock_warning_level' => $this->stock_warning_level,
            'storage_location'    => $this->storage_location,
            'notes'               => $this->notes,
            'is_active'           => $this->is_active,
            'is_low_stock'        => $this->isLowStock(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
