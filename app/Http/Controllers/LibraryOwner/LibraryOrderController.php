<?php

namespace App\Http\Controllers\LibraryOwner;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\GeneralNotification;

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
                    'accepted' => Order::where('library_owner_id', $user->id)->whereIn('status', ['accepted', 'completed'])->count(),
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
            $book = $order->book;
            if ($book->quantity < $order->quantity) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن قبول الطلب لأن الكمية المطلوبة غير متوفرة في المخزون',
                    'message_en' => 'Cannot accept order because the requested quantity is not available in stock',
                ], 400);
            }



            // تقليل المخزون عند القبول
            $book->quantity -= $order->quantity;
            $book->save();

            // فحص المخزون وإرسال إشعار فوري للمالك في حال انخفاضه أو نفاده
            if ($book->quantity == 0) {
                try {
                    $user->notify(new GeneralNotification(
                        'OUT_OF_STOCK',
                        "🚨 نفاد مخزون كتاب",
                        "🚨 Book Out of Stock",
                        "🚨 لقد نفد مخزون كتاب \"{$book->title}\" بالكامل.",
                        "🚨 The stock for \"{$book->title}\" is completely out.",
                        ['book_id' => $book->id]
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send out of stock notification to owner: ' . $e->getMessage());
                }
            } elseif ($book->quantity <= 5) {
                try {
                    $user->notify(new GeneralNotification(
                        'LOW_STOCK',
                        "⚠️ انخفاض مخزون كتاب",
                        "⚠️ Low Book Stock",
                        "⚠️ مخزون كتاب \"{$book->title}\" منخفض حالياً (المتبقي: {$book->quantity} نسخة).",
                        "⚠️ Stock for \"{$book->title}\" is low (remaining: {$book->quantity} copies).",
                        ['book_id' => $book->id]
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send low stock notification to owner: ' . $e->getMessage());
                }
            }

            $order->status = 'accepted';
            $order->accepted_at = now();
            $order->save();

            // الدفع والتوزيع المالي عبر المحفظة
            if ($order->payment_method === 'wallet') {
                // إضافة الأرباح لصاحب المكتبة (الرصيد تم خصمه مسبقاً من محفظة الزبون عند إنشاء الطلب)
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

            // Notify customer of order approval
            try {
                $order->customer->notify(new GeneralNotification(
                    'ORDER_APPROVED',
                    "✅ تم قبول الطلب",
                    "✅ Order Approved",
                    "✅ تم قبول طلبك لشراء كتاب: \"{$order->book->title}\" بنجاح.",
                    "✅ Your request to buy the book \"{$order->book->title}\" has been approved.",
                    ['order_id' => $order->id, 'book_id' => $order->book_id]
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send order approval notification: ' . $e->getMessage());
            }

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

            // استرداد المبلغ للمحفظة إذا كان الدفع عبر المحفظة
            if ($order->payment_method === 'wallet') {
                // التحقق من عدم الاسترداد المكرر للطلب
                $alreadyRefunded = WalletTransaction::where('order_id', $order->id)
                    ->where('type', 'PURCHASE_REFUND')
                    ->exists();

                if (!$alreadyRefunded) {
                    $customer = $order->customer;
                    $balanceBefore = $customer->wallet_balance;
                    $customer->wallet_balance += $order->price;
                    $customer->save();

                    WalletTransaction::create([
                        'user_id' => $customer->id,
                        'type' => 'PURCHASE_REFUND',
                        'amount' => $order->price,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $customer->wallet_balance,
                        'description' => "استرداد قيمة كتاب (رفض الطلب): {$order->book->title}",
                        'order_id' => $order->id,
                    ]);
                }
            }

            DB::commit();

            // Notify customer of order rejection
            try {
                $order->customer->notify(new GeneralNotification(
                    'ORDER_REJECTED',
                    "❌ تم رفض الطلب",
                    "❌ Order Rejected",
                    $order->payment_method === 'wallet'
                        ? "تم رفض الطلب وتمت إعادة المبلغ إلى محفظتك."
                        : "❌ تم رفض طلبك.",
                    $order->payment_method === 'wallet'
                        ? "Your order was rejected and the amount has been refunded to your wallet."
                        : "❌ Your request has been rejected.",
                    ['order_id' => $order->id, 'book_id' => $order->book_id]
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send order rejection notification: ' . $e->getMessage());
            }

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

        // Notify customer of order completion
        try {
            $order->customer->notify(new GeneralNotification(
                'ORDER_COMPLETED',
                "🎉 تم إكمال الطلب",
                "🎉 Order Completed",
                "🎉 تم إكمال طلبك بنجاح.",
                "🎉 Your order has been completed successfully.",
                ['order_id' => $order->id, 'book_id' => $order->book_id]
            ));
        } catch (\Exception $e) {
            \Log::error('Failed to send order completion notification: ' . $e->getMessage());
        }

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
