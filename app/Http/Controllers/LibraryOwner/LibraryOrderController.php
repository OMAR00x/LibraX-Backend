<?php

namespace App\Http\Controllers\LibraryOwner;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryOrderController extends Controller
{
    /**
     * Get library owner's orders
     * طلبات المكتبة
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'status' => 'nullable|in:pending,accepted,rejected,cancelled,completed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Order::with(['book', 'customer'])
            ->where('library_owner_id', $user->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'orders' => OrderResource::collection($orders),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
                'counts' => [
                    'pending' => Order::where('library_owner_id', $user->id)->pending()->count(),
                    'accepted' => Order::where('library_owner_id', $user->id)->accepted()->count(),
                    'rejected' => Order::where('library_owner_id', $user->id)->rejected()->count(),
                ],
            ],
        ]);
    }

    /**
     * Get single order details
     * تفاصيل طلب واحد
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with(['book', 'customer'])
            ->where('library_owner_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }

    /**
     * Accept order
     * قبول الطلب
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with(['book', 'customer'])->where('library_owner_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن قبول هذا الطلب',
                'message_en' => 'Cannot accept this order',
            ], 400);
        }

        DB::beginTransaction();
        try {
            if ($order->payment_method === 'wallet') {
                $customer = $order->customer;
                if ($customer->wallet_balance < $order->price) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'رصيد محفظة الزبون غير كافٍ لإتمام عملية الشراء',
                        'message_en' => 'Customer wallet balance is insufficient to complete the purchase',
                    ], 400);
                }
            }

            $order->status = 'accepted';
            $order->accepted_at = now();
            $order->save();

            // الدفع والتوزيع المالي عبر المحفظة
            if ($order->payment_method === 'wallet') {
                $customer = $order->customer;

                // 1. الخصم من محفظة الزبون
                $customerBalanceBefore = $customer->wallet_balance;
                $customer->wallet_balance -= $order->price;
                $customer->save();

                WalletTransaction::create([
                    'user_id' => $customer->id,
                    'type' => 'purchase',
                    'amount' => $order->price,
                    'balance_before' => $customerBalanceBefore,
                    'balance_after' => $customer->wallet_balance,
                    'description' => "شراء كتاب: {$order->book->title}",
                    'order_id' => $order->id,
                ]);

                // 2. إضافة الأرباح لصاحب المكتبة
                $ownerBalanceBefore = $user->wallet_balance;
                $user->wallet_balance += $order->price;
                $user->save();

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'earning',
                    'amount' => $order->price,
                    'balance_before' => $ownerBalanceBefore,
                    'balance_after' => $user->wallet_balance,
                    'description' => "ربح من بيع: {$order->book->title}",
                    'order_id' => $order->id,
                ]);
            }

            // زيادة عدد المبيعات
            $order->book->increment('total_sales');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم قبول الطلب بنجاح',
                'message_en' => 'Order accepted successfully',
                'data' => [
                    'order' => new OrderResource($order->load(['book', 'customer'])),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Accept Order Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء قبول الطلب: ' . $e->getMessage(),
                'message_en' => 'Error accepting order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject order
     * رفض الطلب
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $order = Order::where('library_owner_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن رفض هذا الطلب',
                'message_en' => 'Cannot reject this order',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'rejected';
            $order->rejection_reason = $request->rejection_reason ?? 'تم رفض الطلب من قبل صاحب المكتبة';
            $order->rejected_at = now();
            $order->save();

            // إرجاع الكمية
            $order->book->increment('quantity');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم رفض الطلب بنجاح',
                'message_en' => 'Order rejected successfully',
                'data' => [
                    'order' => new OrderResource($order->load(['book', 'customer'])),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Reject Order Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء رفض الطلب: ' . $e->getMessage(),
                'message_en' => 'Error rejecting order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark order as completed
     * تعليم الطلب كمكتمل
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('library_owner_id', $user->id)->findOrFail($id);

        if ($order->status !== 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن إكمال هذا الطلب',
                'message_en' => 'Cannot complete this order',
            ], 400);
        }

        $order->status = 'completed';
        $order->completed_at = now();
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم إكمال الطلب بنجاح',
            'message_en' => 'Order completed successfully',
            'data' => [
                'order' => new OrderResource($order->load(['book', 'customer'])),
            ],
        ]);
    }
}
