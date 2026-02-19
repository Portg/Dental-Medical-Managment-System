<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MemberResource;
use App\Http\Resources\MemberTransactionResource;
use App\Patient;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemberController extends ApiController
{
    public function __construct(
        protected MemberService $service
    ) {
        $this->middleware('can:manage-members');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Patient::with('memberLevel')
            ->whereNull('deleted_at')
            ->where('member_status', '!=', 'Inactive')
            ->whereNotNull('member_no');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('surname', 'like', "%{$search}%")
                  ->orWhere('othername', 'like', "%{$search}%")
                  ->orWhere('member_no', 'like', "%{$search}%")
                  ->orWhere('phone_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('level_id')) {
            $query->where('member_level_id', $request->input('level_id'));
        }

        if ($request->filled('status')) {
            $query->where('member_status', $request->input('status'));
        }

        $paginator = $query->orderBy('member_since', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, MemberResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $patient = Patient::with('memberLevel')->find($id);

        if (!$patient || !$patient->member_no) {
            return $this->error('Member not found', 404);
        }

        return $this->success(new MemberResource($patient));
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id'      => 'required|exists:patients,id',
            'member_level_id' => 'required|exists:member_levels,id',
            'initial_balance' => 'nullable|numeric|min:0',
            'payment_method'  => 'nullable|string|max:50',
            'member_expiry'   => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->registerMember($request->only([
            'patient_id', 'member_level_id', 'initial_balance', 'payment_method', 'member_expiry',
        ]));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        $patient = Patient::with('memberLevel')->find($request->input('patient_id'));

        return $this->success(new MemberResource($patient), $result['message'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'member_level_id' => 'required|exists:member_levels,id',
            'member_status'   => 'nullable|string|in:Active,Suspended,Expired',
            'member_expiry'   => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->updateMember($id, $request->only([
            'member_level_id', 'member_status', 'member_expiry',
        ]));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        $patient = Patient::with('memberLevel')->find($id);

        return $this->success(new MemberResource($patient), $result['message']);
    }

    public function deposit(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'description'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->deposit($id, $request->only([
            'amount', 'payment_method', 'description',
        ]));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success([
            'new_balance' => $result['new_balance'],
        ], $result['message']);
    }

    public function transactions(int $id): JsonResponse
    {
        $transactions = $this->service->getTransactions($id);

        return $this->success($transactions);
    }
}
