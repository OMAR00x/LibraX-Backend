<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Book;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get customer's orders (Purchase History)
     * سجل المشتريات للزبون
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'status' => 'nullable|in:pending,accepted,rejected,cancelled,completed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Order::with(['book', 'libraryOwner'])
            ->where('customer_id', $user->id);

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
            ],
        ]);
    }

    /**
     * Create new order (Purchase book)
     * شراء كتاب
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'payment_method' => 'required|in:cash,wallet',
        ]);

        $user = $request->user();
        $book = Book::with('libraryOwner')->findOrFail($request->book_id);

        // التحقق من توفر الكتاب
        if (!$book->is_active || $book->quantity <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'الكتاب غير متوفر حالياً',
                'message_en' => 'Book is not available',
            ], 400);
        }

        // التحقق من الرصيد إذا كان الدفع عبر المحفظة
        if ($request->payment_method === 'wallet') {
            if ($user->wallet_balance < $book->price) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'رصيدك غير كافٍ',
                    'message_en' => 'Insufficient balance',
                    'required' => (float) $book->price,
                    'available' => (float) $user->wallet_balance,
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // إنشاء الطلب
            $order = Order::create([
                'customer_id' => $user->id,
                'book_id' => $book->id,
                'library_owner_id' => $book->library_owner_id,
                'price' => $book->price,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            // إذا كان الدفع عبر المحفظة، خصم المبلغ
            if ($request->payment_method === 'wallet') {
                $balanceBefore = $user->wallet_balance;
                $user->wallet_balance -= $book->price;
                $user->save();

                // تسجيل المعاملة
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'purchase',
                    'amount' => $book->price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->wallet_balance,
                    'description' => "شراء كتاب: {$book->title}",
                    'order_id' => $order->id,
                ]);
            }

            // تقليل الكمية
            $book->decrement('quantity');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء الطلب بنجاح',
                'message_en' => 'Order created successfully',
                'data' => [
                    'order' => new OrderResource($order->load(['book', 'libraryOwner'])),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
                'message_en' => 'Error creating order',
            ], 500);
        }
    }

    /**
     * Get single order details
     * تفاصيل طلب واحد
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with(['book', 'libraryOwner'])
            ->where('customer_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }

    /**
     * Cancel order (only if pending)
     * إلغاء الطلب
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $order = Order::where('customer_id', $user->id)->findOrFail($id);

        if (!$order->canBeCancelled()) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن إلغاء هذا الطلب',
                'message_en' => 'Cannot cancel this order',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';
            $order->cancellation_reason = $request->cancellation_reason;
            $order->cancelled_at = now();
            $order->save();

            // إرجاع المبلغ إذا كان الدفع عبر المحفظة
            if ($order->payment_method === 'wallet') {
                $balanceBefore = $user->wallet_balance;
                $user->wallet_balance += $order->price;
                $user->save();

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'refund',
                    'amount' => $order->price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->wallet_balance,
                    'description' => "استرجاع مبلغ الطلب #{$order->id}",
                    'order_id' => $order->id,
                ]);
            }

            // إرجاع الكمية
            $order->book->increment('quantity');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم إلغاء الطلب بنجاح',
                'message_en' => 'Order cancelled successfully',
                'data' => [
                    'order' => new OrderResource($order->load(['book', 'libraryOwner'])),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إلغاء الطلب',
                'message_en' => 'Error cancelling order',
            ], 500);
        }
    }

    /**
     * Rate completed order
     * تقييم الطلب المكتمل
     */
    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $order = Order::with('book')->where('customer_id', $user->id)->findOrFail($id);

        if (!$order->canBeRated()) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن تقييم هذا الطلب',
                'message_en' => 'Cannot rate this order',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->rating = $request->rating;
            $order->review = $request->review;
            $order->save();

            // تحديث تقييم الكتاب
            $book = $order->book;
            $totalRatings = $book->total_ratings + 1;
            $newAverage = (($book->average_rating * $book->total_ratings) + $request->rating) / $totalRatings;

            $book->average_rating = round($newAverage, 2);
            $book->total_ratings = $totalRatings;
            $book->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم تقييم الطلب بنجاح',
                'message_en' => 'Order rated successfully',
                'data' => [
                    'order' => new OrderResource($order->load(['book', 'libraryOwner'])),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء التقييم',
                'message_en' => 'Error rating order',
            ], 500);
        }
    }
}
