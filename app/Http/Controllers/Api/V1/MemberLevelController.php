<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MemberLevelResource;
use App\MemberLevel;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Member Levels
 */
class MemberLevelController extends ApiController
{
    public function __construct(
        protected MemberService $service
    ) {
        $this->middleware('can:manage-members');
    }

    public function index(): JsonResponse
    {
        $levels = $this->service->getLevelList();

        return $this->success(MemberLevelResource::collection($levels));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:100',
            'code'            => 'required|string|max:50|unique:member_levels,code',
            'discount_rate'   => 'required|numeric|min:0|max:100',
            'color'           => 'nullable|string|max:20',
            'min_consumption' => 'nullable|numeric|min:0',
            'points_rate'     => 'nullable|numeric|min:0',
            'benefits'        => 'nullable|string',
            'sort_order'      => 'nullable|integer|min:0',
            'is_default'      => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->createLevel($request->only([
            'name', 'code', 'discount_rate', 'color', 'min_consumption',
            'points_rate', 'benefits', 'sort_order', 'is_default', 'is_active',
        ]));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(null, $result['message'], 201);
    }

    public function show(int $id): JsonResponse
    {
        $level = $this->service->getLevel($id);

        return $this->success(new MemberLevelResource($level));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:100',
            'code'            => 'required|string|max:50|unique:member_levels,code,' . $id,
            'discount_rate'   => 'required|numeric|min:0|max:100',
            'color'           => 'nullable|string|max:20',
            'min_consumption' => 'nullable|numeric|min:0',
            'points_rate'     => 'nullable|numeric|min:0',
            'benefits'        => 'nullable|string',
            'sort_order'      => 'nullable|integer|min:0',
            'is_default'      => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->updateLevel($id, $request->only([
            'name', 'code', 'discount_rate', 'color', 'min_consumption',
            'points_rate', 'benefits', 'sort_order', 'is_default', 'is_active',
        ]));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(new MemberLevelResource($this->service->getLevel($id)), $result['message']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteLevel($id);

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(null, $result['message']);
    }
}
