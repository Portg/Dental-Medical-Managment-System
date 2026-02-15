<?php

namespace App\Services;

use App\MedicalCardItem;

class MedicalCardItemService
{
    /**
     * Delete a medical card item.
     */
    public function deleteMedicalCardItem(int $id): bool
    {
        return (bool) MedicalCardItem::where('id', $id)->delete();
    }
}
