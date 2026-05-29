<?php

namespace App\Http\Controllers\LibraryOwner;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use Illuminate\Http\Request;

class LibraryStatisticsController extends Controller
{
    /**
     * Get library statistics
     * إحصائيات المكتبة
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // إجمالي الكتب
        $totalBooks = Book::where('library_owner_id', $user->id)->count();

        // إجمالي النسخ المتوفرة
        $totalCopies = Book::where('library_owner_id', $user->id)->sum('quantity');

        // إجمالي المبيعات
        $totalSales = Book::where('library_owner_id', $user->id)->sum('total_sales');

        // إجمالي الأرباح (من الطلبات المكتملة عبر المحفظة)
        $totalRevenue = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->where('payment_method', 'wallet')
            ->sum('price');

        // الطلبات قيد المراجعة
        $pendingOrders = Order::where('library_owner_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // أكثر الكتب مبيعاً
        $topSellingBooks = Book::where('library_owner_id', $user->id)
            ->orderBy('total_sales', 'desc')
            ->take(5)
            ->get(['id', 'title', 'total_sales', 'cover_image']);

        // أعلى الكتب تقييماً
        $topRatedBooks = Book::where('library_owner_id', $user->id)
            ->where('total_ratings', '>', 0)
            ->orderBy('average_rating', 'desc')
            ->take(5)
            ->get(['id', 'title', 'average_rating', 'total_ratings', 'cover_image']);

        // إحصائيات الطلبات
        $ordersStats = [
            'total' => Order::where('library_owner_id', $user->id)->count(),
            'pending' => Order::where('library_owner_id', $user->id)->pending()->count(),
            'accepted' => Order::where('library_owner_id', $user->id)->accepted()->count(),
            'completed' => Order::where('library_owner_id', $user->id)->completed()->count(),
            'rejected' => Order::where('library_owner_id', $user->id)->rejected()->count(),
            'cancelled' => Order::where('library_owner_id', $user->id)->cancelled()->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_books' => $totalBooks,
                    'total_copies' => $totalCopies,
                    'total_sales' => $totalSales,
                    'total_revenue' => (float) $totalRevenue,
                    'pending_orders' => $pendingOrders,
                    'wallet_balance' => (float) $user->wallet_balance,
                ],
                'orders_stats' => $ordersStats,
                'top_selling_books' => $topSellingBooks->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'total_sales' => $book->total_sales,
                        'cover_image' => $book->cover_image ? url('storage/' . $book->cover_image) : null,
                    ];
                }),
                'top_rated_books' => $topRatedBooks->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'average_rating' => (float) $book->average_rating,
                        'total_ratings' => $book->total_ratings,
                        'cover_image' => $book->cover_image ? url('storage/' . $book->cover_image) : null,
                    ];
                }),
            ],
        ]);
    }
}
