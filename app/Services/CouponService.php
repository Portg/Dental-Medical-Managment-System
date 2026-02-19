<?php

namespace App\Services;

use App\Coupon;
use App\CouponUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CouponService
{
    /**
     * Get all coupons for DataTables listing.
     */
    public function getCouponList(): Collection
    {
        return Coupon::orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new coupon.
     */
    public function createCoupon(array $data): Coupon
    {
        return Coupon::create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'max_discount' => $data['max_discount'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? 1,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? 0,
            '_who_added' => Auth::id(),
        ]);
    }

    /**
     * Get coupon with usages for detail view.
     */
    public function getCouponDetail(int $id): Coupon
    {
        return Coupon::with('usages.patient', 'usages.invoice')->findOrFail($id);
    }

    /**
     * Get a coupon for editing.
     */
    public function getCouponForEdit(int $id): Coupon
    {
        return Coupon::findOrFail($id);
    }

    /**
     * Update an existing coupon.
     */
    public function updateCoupon(int $id, array $data): bool
    {
        $coupon = Coupon::findOrFail($id);

        return (bool) $coupon->update([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'max_discount' => $data['max_discount'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? 1,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? 0,
        ]);
    }

    /**
     * Delete a coupon (soft-delete if used, hard-delete otherwise).
     */
    public function deleteCoupon(int $id): bool
    {
        $coupon = Coupon::findOrFail($id);

        if ($coupon->used_count > 0) {
            $coupon->deleted_at = now();
            $coupon->save();
        } else {
            $coupon->delete();
        }

        return true;
    }

    /**
     * Validate a coupon code and return discount info.
     *
     * @return array{valid: bool, message: string, coupon?: array}
     */
    public function validateCoupon(string $code, float $orderAmount = 0, ?int $patientId = null): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => __('invoices.coupon_invalid')];
        }

        if (!$coupon->isValid($orderAmount, $patientId)) {
            $message = $this->getInvalidReason($coupon, $orderAmount, $patientId);
            return ['valid' => false, 'message' => $message];
        }

        $discount = $coupon->calculateDiscount($orderAmount);

        return [
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'discount' => $discount,
            ],
            'message' => __('invoices.coupon_applied'),
        ];
    }

    /**
     * Determine the specific reason a coupon is invalid.
     */
    private function getInvalidReason(Coupon $coupon, float $orderAmount, ?int $patientId): string
    {
        if (!$coupon->is_active) {
            return __('invoices.coupon_inactive');
        }
        if ($coupon->expires_at && $coupon->expires_at < now()) {
            return __('invoices.coupon_expired');
        }
        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return __('invoices.coupon_exhausted');
        }
        if ($coupon->min_order_amount > 0 && $orderAmount < $coupon->min_order_amount) {
            return __('invoices.coupon_min_amount', ['amount' => number_format($coupon->min_order_amount, 2)]);
        }
        if ($patientId && $coupon->max_uses_per_user) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('patient_id', $patientId)
                ->count();
            if ($userUsage >= $coupon->max_uses_per_user) {
                return __('invoices.coupon_user_limit_reached');
            }
        }

        return __('invoices.coupon_invalid');
    }
}
