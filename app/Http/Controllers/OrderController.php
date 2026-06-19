<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Book;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\GeneralNotification;

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
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,wallet',
        ]);

        $user = $request->user();
        $book = Book::with('libraryOwner')->findOrFail($request->book_id);
        $quantity = (int) $request->quantity;

        // التحقق من توفر الكتاب والكمية المطلوبة
        if (!$book->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'الكتاب غير متوفر حالياً',
                'message_en' => 'Book is not available',
            ], 400);
        }

        if ($book->quantity < $quantity) {
            return response()->json([
                'status' => 'error',
                'message' => "الكمية المطلوبة غير متوفرة، المتبقي فقط {$book->quantity} نسخة.",
                'message_en' => "Requested quantity is not available. Only {$book->quantity} copies remain.",
            ], 400);
        }

        $totalPrice = $book->price * $quantity;

        // التحقق من الرصيد إذا كان الدفع عبر المحفظة
        if ($request->payment_method === 'wallet' && !config('app.test_wallet_mode', false)) {
            if ($user->wallet_balance < $totalPrice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'رصيدك غير كافٍ',
                    'message_en' => 'Insufficient balance',
                    'required' => (float) $totalPrice,
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
                'price' => $totalPrice,
                'quantity' => $quantity,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            DB::commit();

            // Notify library owner of the new purchase
            try {
                $owner = $book->libraryOwner;
                if ($owner) {
                    $owner->notify(new GeneralNotification(
                        'NEW_PURCHASE',
                        "🛍️ طلب شراء جديد",
                        "🛍️ New Purchase Request",
                        "🛍️ تم استلام طلب جديد لشراء كتاب \"{$book->title}\".",
                        "🛍️ A new order has been received for \"{$book->title}\".",
                        ['order_id' => $order->id, 'book_id' => $book->id]
                    ));
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send purchase notification: ' . $e->getMessage());
            }

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

            DB::commit();

            // Notify customer of order cancellation
            try {
                $order->customer->notify(new GeneralNotification(
                    'ORDER_CANCELLED',
                    "⚠️ تم إلغاء الطلب",
                    "⚠️ Order Cancelled",
                    "⚠️ تم إلغاء طلبك لكتاب \"{$order->book->title}\".",
                    "⚠️ Your request for \"{$order->book->title}\" has been cancelled.",
                    ['order_id' => $order->id, 'book_id' => $order->book_id]
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send order cancellation notification to customer: ' . $e->getMessage());
            }

            // Notify library owner of order cancellation
            try {
                $owner = $order->libraryOwner;
                if ($owner) {
                    $owner->notify(new GeneralNotification(
                        'ORDER_CANCELLED',
                        "⚠️ تم إلغاء طلب شراء",
                        "⚠️ Purchase Order Cancelled",
                        "⚠️ قام الزبون \"{$user->name}\" بإلغاء طلب شراء كتاب \"{$order->book->title}\".",
                        "⚠️ Customer \"{$user->name}\" cancelled the order for \"{$order->book->title}\".",
                        ['order_id' => $order->id, 'book_id' => $order->book_id]
                    ));
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send order cancellation notification to library owner: ' . $e->getMessage());
            }

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

            // Create or update the BookReview record dynamically
            \App\Models\BookReview::updateOrCreate(
                ['user_id' => $user->id, 'book_id' => $order->book_id],
                [
                    'rating' => $request->rating,
                    'review_content' => $request->review,
                ]
            );

            // Recalculate book average rating from the reviews table to keep 100% consistency
            $stats = \App\Models\BookReview::where('book_id', $order->book_id)
                ->select(DB::raw('count(*) as count'), DB::raw('avg(rating) as avg'))
                ->first();

            $book = $order->book;
            $book->average_rating = round($stats->avg ?? 0, 2);
            $book->total_ratings = $stats->count ?? 0;
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
