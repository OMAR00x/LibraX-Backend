<?php

namespace App\Http\Controllers\LibraryOwner;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // إجمالي الأرباح (من الطلبات المكتملة)
        $totalRevenue = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->sum('price');

        // الطلبات قيد المراجعة
        $pendingOrders = Order::where('library_owner_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // أكثر الكتب مبيعاً
        $topSellingBooks = Book::where('library_owner_id', $user->id)
            ->orderBy('total_sales', 'desc')
            ->take(5)
            ->get(['id', 'title', 'author', 'total_sales', 'cover_image']);

        // أعلى الكتب تقييماً
        $topRatedBooks = Book::where('library_owner_id', $user->id)
            ->where('total_ratings', '>', 0)
            ->orderBy('average_rating', 'desc')
            ->take(5)
            ->get(['id', 'title', 'author', 'average_rating', 'total_ratings', 'cover_image']);

        // إحصائيات الطلبات
        $ordersStats = [
            'total' => Order::where('library_owner_id', $user->id)->count(),
            'pending' => Order::where('library_owner_id', $user->id)->pending()->count(),
            'accepted' => Order::where('library_owner_id', $user->id)->whereIn('status', ['accepted', 'completed'])->count(),
            'completed' => Order::where('library_owner_id', $user->id)->whereIn('status', ['accepted', 'completed'])->count(),
            'rejected' => Order::where('library_owner_id', $user->id)->rejected()->count(),
            'cancelled' => Order::where('library_owner_id', $user->id)->cancelled()->count(),
        ];

        // المقاييس التفصيلية المطلوبة للواجهة
        $todayStart = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7)->startOfDay();
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        // مبيعات اليوم
        $salesToday = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $todayStart)
            ->count();

        // مبيعات الأسبوع
        $salesThisWeek = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $sevenDaysAgo)
            ->count();

        // مبيعات الشهر
        $salesThisMonth = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $thirtyDaysAgo)
            ->count();

        // أرباح اليوم
        $revenueToday = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $todayStart)
            ->sum('price');

        // أرباح الشهر
        $revenueThisMonth = Order::where('library_owner_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $thirtyDaysAgo)
            ->sum('price');

        // إجمالي الزبائن الفريدين
        $totalCustomers = Order::where('library_owner_id', $user->id)
            ->distinct('customer_id')
            ->count('customer_id');

        // الزبائن الجدد (أول طلب لهم خلال آخر 7 أيام)
        $newCustomers = Order::where('library_owner_id', $user->id)
            ->groupBy('customer_id')
            ->havingRaw('MIN(created_at) >= ?', [$sevenDaysAgo])
            ->get(['customer_id'])
            ->count();

        // بيانات المخطط البياني حسب الفترة
        $period = $request->query('period', 'weekly');
        $weeklyRevenue = [];

        if ($period === 'yearly') {
            $startDate = now()->subMonths(11)->startOfMonth();
            $rawRevenue = Order::where('library_owner_id', $user->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->select(
                    DB::raw("DATE_FORMAT(completed_at, '%Y-%m') as month"),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('month')
                ->pluck('revenue', 'month')
                ->toArray();

            for ($i = 11; $i >= 0; $i--) {
                $monthKey = now()->subMonths($i)->format('Y-m');
                $monthName = now()->subMonths($i)->format('F');
                $weeklyRevenue[] = [
                    'day_name' => $monthName,
                    'revenue' => (float) ($rawRevenue[$monthKey] ?? 0.0),
                ];
            }
        } elseif ($period === 'monthly') {
            $startDate = now()->subDays(27)->startOfDay();
            $rawRevenue = Order::where('library_owner_id', $user->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->select(
                    DB::raw('FLOOR(DATEDIFF(NOW(), completed_at) / 7) as week_index'),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('week_index')
                ->pluck('revenue', 'week_index')
                ->toArray();

            for ($i = 3; $i >= 0; $i--) {
                $weeklyRevenue[] = [
                    'day_name' => 'W' . (4 - $i),
                    'revenue' => (float) ($rawRevenue[$i] ?? 0.0),
                ];
            }
        } else { // weekly
            $startDate = now()->subDays(6)->startOfDay();
            $rawRevenue = Order::where('library_owner_id', $user->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(completed_at) as date'),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('date')
                ->pluck('revenue', 'date')
                ->toArray();

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayName = now()->subDays($i)->format('l');
                $weeklyRevenue[] = [
                    'day_name' => $dayName,
                    'revenue' => (float) ($rawRevenue[$date] ?? 0.0),
                ];
            }
        }

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
                    'sales_today' => $salesToday,
                    'sales_this_week' => $salesThisWeek,
                    'sales_this_month' => $salesThisMonth,
                    'revenue_today' => (float) $revenueToday,
                    'revenue_this_month' => (float) $revenueThisMonth,
                    'total_customers' => $totalCustomers,
                    'new_customers' => $newCustomers,
                ],
                'orders_stats' => $ordersStats,
                'weekly_revenue' => $weeklyRevenue,
                'top_selling_books' => $topSellingBooks->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'total_sales' => $book->total_sales,
                        'cover_image' => $book->cover_image ? url('storage/' . $book->cover_image) : null,
                    ];
                }),
                'top_rated_books' => $topRatedBooks->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'average_rating' => (float) $book->average_rating,
                        'total_ratings' => $book->total_ratings,
                        'cover_image' => $book->cover_image ? url('storage/' . $book->cover_image) : null,
                    ];
                }),
            ],
        ]);
    }
}
