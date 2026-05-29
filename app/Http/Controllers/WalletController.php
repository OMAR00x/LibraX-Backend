<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChargeRequestResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\ChargeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WalletController extends Controller
{
    /**
     * Get wallet balance and recent transactions
     * رصيد المحفظة والمعاملات الأخيرة
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = $user->walletTransactions()
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'balance' => (float) $user->wallet_balance,
                'recent_transactions' => WalletTransactionResource::collection($transactions),
            ],
        ]);
    }

    /**
     * Get all wallet transactions
     * جميع معاملات المحفظة
     */
    public function transactions(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'type' => 'nullable|in:charge,purchase,refund,earning',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->walletTransactions();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'transactions' => WalletTransactionResource::collection($transactions),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ],
        ]);
    }

    /**
     * Request wallet charge
     * طلب شحن المحفظة
     */
    public function requestCharge(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:1000000',
            'transaction_number' => 'required|string|max:255',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // رفع صورة الإيصال إن وجدت
        $receiptPath = null;
        if ($request->hasFile('receipt_image')) {
            $receiptPath = $request->file('receipt_image')->store('receipts', 'public');
        }

        $chargeRequest = ChargeRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'transaction_number' => $request->transaction_number,
            'receipt_image' => $receiptPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب الشحن بنجاح',
            'message_en' => 'Charge request submitted successfully',
            'data' => [
                'charge_request' => new ChargeRequestResource($chargeRequest),
            ],
        ], 201);
    }

    /**
     * Get user's charge requests
     * طلبات الشحن للمستخدم
     */
    public function chargeRequests(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->chargeRequests();

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
            ],
        ]);
    }

    /**
     * Get single charge request
     * تفاصيل طلب شحن واحد
     */
    public function showChargeRequest(Request $request, $id)
    {
        $user = $request->user();

        $chargeRequest = ChargeRequest::where('user_id', $user->id)->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'charge_request' => new ChargeRequestResource($chargeRequest),
            ],
        ]);
    }
}
