<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\ChargeRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStatisticsController extends Controller
{
    /**
     * Get system statistics
     * إحصائيات النظام الكاملة
     */
    public function index(Request $request)
    {
        // إحصائيات المستخدمين
        $usersStats = [
            'total' => User::count(),
            'customers' => User::where('role', 'customer')->count(),
            'library_owners' => User::where('role', 'library_owner')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'active' => User::where('is_active', true)->count(),
        ];

        // إحصائيات الكتب
        $booksStats = [
            'total' => Book::count(),
            'active' => Book::where('is_active', true)->count(),
            'total_copies' => Book::sum('quantity'),
            'total_sales' => Book::sum('total_sales'),
        ];

        // إحصائيات الطلبات
        $ordersStats = [
            'total' => Order::count(),
            'pending' => Order::pending()->count(),
            'accepted' => Order::accepted()->count(),
            'completed' => Order::completed()->count(),
            'rejected' => Order::rejected()->count(),
            'cancelled' => Order::cancelled()->count(),
        ];

        // إحصائيات المحفظة
        $walletStats = [
            'total_balance' => User::sum('wallet_balance'),
            'pending_charges' => ChargeRequest::pending()->count(),
            'approved_charges' => ChargeRequest::approved()->count(),
            'rejected_charges' => ChargeRequest::rejected()->count(),
        ];

        // إجمالي الإيرادات (من الطلبات المكتملة عبر المحفظة)
        $totalRevenue = Order::where('status', 'completed')
            ->where('payment_method', 'wallet')
            ->sum('price');

        // أكثر المكتبات مبيعاً
        $topLibraries = User::where('role', 'library_owner')
            ->withCount(['libraryOrders as completed_orders' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->orderBy('completed_orders', 'desc')
            ->take(5)
            ->get(['id', 'library_name', 'wallet_balance']);

        // أكثر الكتب مبيعاً
        $topBooks = Book::with('libraryOwner')
            ->orderBy('total_sales', 'desc')
            ->take(10)
            ->get(['id', 'title', 'author', 'total_sales', 'library_owner_id', 'cover_image']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => $usersStats,
                'books' => $booksStats,
                'orders' => $ordersStats,
                'wallet' => $walletStats,
                'total_revenue' => (float) $totalRevenue,
                'top_libraries' => $topLibraries->map(function ($library) {
                    return [
                        'id' => $library->id,
                        'name' => $library->library_name,
                        'completed_orders' => $library->completed_orders,
                        'wallet_balance' => (float) $library->wallet_balance,
                    ];
                }),
                'top_books' => $topBooks->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'total_sales' => $book->total_sales,
                        'library_name' => $book->libraryOwner->library_name,
                        'cover_image' => $book->cover_image ? url('storage/' . $book->cover_image) : null,
                    ];
                }),
            ],
        ]);
    }
}
