<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChargeRequestResource;
use App\Models\ChargeRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminChargeRequestController extends Controller
{
    /**
     * Get all charge requests
     * جميع طلبات الشحن
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ChargeRequest::with(['user', 'approvedBy']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $chargeRequests = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'charge_requests' => ChargeRequestResource::collection($chargeRequests),
                'pagination' => [
                    'current_page' => $chargeRequests->currentPage(),
                    'last_page' => $chargeRequests->lastPage(),
                    'per_page' => $chargeRequests->perPage(),
                    'total' => $chargeRequests->total(),
                ],
                'counts' => [
                    'pending' => ChargeRequest::pending()->count(),
                    'approved' => ChargeRequest::approved()->count(),
                    'rejected' => ChargeRequest::rejected()->count(),
                ],
            ],
        ]);
    }

    /**
     * Get single charge request
     * تفاصيل طلب شحن واحد
     */
    public function show($id)
    {
        $chargeRequest = ChargeRequest::with(['user', 'approvedBy'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'charge_request' => new ChargeRequestResource($chargeRequest),
            ],
        ]);
    }

    /**
     * Approve charge request
     * الموافقة على طلب الشحن
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:1000', // يمكن تعديل المبلغ
        ]);

        $admin = $request->user();
        $chargeRequest = ChargeRequest::with('user')->findOrFail($id);

        if ($chargeRequest->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن الموافقة على هذا الطلب',
                'message_en' => 'Cannot approve this request',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // المبلغ المعتمد (إما المبلغ المطلوب أو المبلغ المعدل)
            $approvedAmount = $request->amount ?? $chargeRequest->amount;

            // تحديث حالة الطلب
            $chargeRequest->status = 'approved';
            $chargeRequest->amount = $approvedAmount;
            $chargeRequest->approved_by = $admin->id;
            $chargeRequest->approved_at = now();
            $chargeRequest->save();

            // إضافة المبلغ للمحفظة بشكل atomic مع row lock
            $user = User::where('id', $chargeRequest->user_id)->lockForUpdate()->first();
            $balanceBefore = $user->wallet_balance;
            $user->wallet_balance += $approvedAmount;
            $user->save();

            // تسجيل المعاملة
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'charge',
                'amount' => $approvedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->wallet_balance,
                'description' => 'شحن رصيد - معاملة #' . $chargeRequest->transaction_number,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تمت الموافقة على طلب الشحن بنجاح',
                'message_en' => 'Charge request approved successfully',
                'data' => [
                    'charge_request' => new ChargeRequestResource($chargeRequest->load(['user', 'approvedBy'])),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء الموافقة',
                'message_en' => 'Error approving request',
            ], 500);
        }
    }

    /**
     * Reject charge request
     * رفض طلب الشحن
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $admin = $request->user();
        $chargeRequest = ChargeRequest::findOrFail($id);

        if ($chargeRequest->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن رفض هذا الطلب',
                'message_en' => 'Cannot reject this request',
            ], 400);
        }

        $chargeRequest->status = 'rejected';
        $chargeRequest->rejection_reason = $request->rejection_reason;
        $chargeRequest->approved_by = $admin->id;
        $chargeRequest->rejected_at = now();
        $chargeRequest->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفض طلب الشحن',
            'message_en' => 'Charge request rejected',
            'data' => [
                'charge_request' => new ChargeRequestResource($chargeRequest->load(['user', 'approvedBy'])),
            ],
        ]);
    }
}
