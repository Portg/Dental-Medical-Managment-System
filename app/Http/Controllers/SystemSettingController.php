<?php

namespace App\Http\Controllers;

use App\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    /**
     * Display the unified settings page.
     */
    public function index()
    {
        $clinic = SystemSetting::getGroup('clinic');
        $member = SystemSetting::getGroup('member');

        return view('system_settings.index', compact('clinic', 'member'));
    }

    /**
     * Update settings for a given group.
     */
    public function update(Request $request, string $group): JsonResponse
    {
        $rules = $this->validationRules($group);

        if (!$rules) {
            return response()->json(['message' => __('system_settings.invalid_group'), 'status' => false]);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $keyValues = [];
        foreach (array_keys($rules) as $field) {
            if ($request->has($field)) {
                $keyValues[$group . '.' . $field] = $request->input($field);
            }
        }

        SystemSetting::setMany($keyValues);

        return response()->json(['message' => __('system_settings.saved'), 'status' => true]);
    }

    /**
     * API endpoint: return all settings for a group (for JS consumption).
     */
    public function getGroup(string $group): JsonResponse
    {
        $settings = SystemSetting::getGroup($group);
        return response()->json($settings);
    }

    private function validationRules(string $group): ?array
    {
        return match ($group) {
            'clinic' => [
                'start_time'             => 'required|date_format:H:i',
                'end_time'               => 'required|date_format:H:i|after:start_time',
                'slot_interval'          => 'required|integer|in:15,30,60',
                'default_duration'       => 'required|integer|min:15|max:240',
                'grid_start_hour'        => 'required|integer|min:0|max:23',
                'grid_end_hour'          => 'required|integer|min:1|max:24',
                'hide_off_duty_doctors'  => 'required|boolean',
                'show_appointment_notes' => 'required|boolean',
                'allow_overbooking'      => 'required|boolean',
                'max_advance_days'       => 'required|integer|min:0',
                'min_advance_hours'      => 'required|integer|min:0',
            ],
            'member' => [
                'points_enabled'         => 'required|boolean',
                'points_expiry_days'     => 'required|integer|min:0',
                'card_number_mode'       => 'required|in:auto,phone,manual',
                'referral_bonus_enabled' => 'required|boolean',
                'points_exchange_rate'   => 'required|integer|min:1',
                'points_exchange_enabled'=> 'required|boolean',
            ],
            default => null,
        };
    }
}
