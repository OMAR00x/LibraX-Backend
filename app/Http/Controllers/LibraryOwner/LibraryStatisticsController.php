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

        // إجمالي المبيعات (مجموع كميات الطلبات المقبولة والمكتملة)
        $totalSales = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->sum('quantity');

        // إجمالي الأرباح (من الطلبات المقبولة والمكتملة)
        $totalRevenue = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->sum('price');

        // الطلبات قيد المراجعة
        $pendingOrders = Order::where('library_owner_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // أكثر الكتب مبيعاً (من تفاصيل الطلبات المقبولة والمكتملة)
        $topSellingBooksRaw = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->select('book_id', DB::raw('SUM(quantity) as total_sales'))
            ->groupBy('book_id')
            ->orderBy('total_sales', 'desc')
            ->take(5)
            ->get();

        $topSellingBookIds = $topSellingBooksRaw->pluck('book_id')->toArray();
        $topSellingSalesMap = $topSellingBooksRaw->pluck('total_sales', 'book_id')->toArray();

        $topSellingBooks = Book::whereIn('id', $topSellingBookIds)
            ->get(['id', 'title', 'author', 'cover_image'])
            ->map(function ($book) use ($topSellingSalesMap) {
                $book->total_sales = (int) ($topSellingSalesMap[$book->id] ?? 0);
                return $book;
            })
            ->sortByDesc('total_sales')
            ->values();

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
            'accepted' => Order::where('library_owner_id', $user->id)->where('status', 'accepted')->count(),
            'completed' => Order::where('library_owner_id', $user->id)->where('status', 'completed')->count(),
            'rejected' => Order::where('library_owner_id', $user->id)->rejected()->count(),
            'cancelled' => Order::where('library_owner_id', $user->id)->cancelled()->count(),
        ];

        // المقاييس التفصيلية المطلوبة للواجهة
        $todayStart = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7)->startOfDay();
        $monthStart = now()->startOfMonth();

        // مبيعات اليوم
        $salesToday = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->where('accepted_at', '>=', $todayStart)
            ->sum('quantity');

        // مبيعات الأسبوع
        $salesThisWeek = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->where('accepted_at', '>=', $sevenDaysAgo)
            ->sum('quantity');

        // مبيعات الشهر
        $salesThisMonth = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->where('accepted_at', '>=', $monthStart)
            ->sum('quantity');

        // أرباح اليوم
        $revenueToday = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->where('accepted_at', '>=', $todayStart)
            ->sum('price');

        // أرباح الشهر
        $revenueThisMonth = Order::where('library_owner_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->where('accepted_at', '>=', $monthStart)
            ->sum('price');

        // بيانات المخطط البياني حسب الفترة
        $period = $request->query('period', 'weekly');
        $weeklyRevenue = [];
        $locale = $request->header('Accept-Language') === 'en' ? 'en' : 'ar';

        if ($period === 'daily') {
            $startDate = now()->subHours(23)->startOfHour();
            $rawRevenue = Order::where('library_owner_id', $user->id)
                ->whereIn('status', ['accepted', 'completed'])
                ->where('accepted_at', '>=', $startDate)
                ->select(
                    DB::raw("DATE_FORMAT(accepted_at, '%Y-%m-%d %H:00') as hour_key"),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('hour_key')
                ->pluck('revenue', 'hour_key')
                ->toArray();

            for ($i = 23; $i >= 0; $i--) {
                $time = now()->subHours($i);
                $hourKey = $time->format('Y-m-d H:00');
                $label = $time->format('H:00');
                $weeklyRevenue[] = [
                    'day_name' => $label,
                    'revenue' => (float) ($rawRevenue[$hourKey] ?? 0.0),
                ];
            }
        } elseif ($period === 'monthly') {
            $startDate = now()->startOfMonth();
            $daysInMonth = now()->daysInMonth;
            
            $rawRevenue = Order::where('library_owner_id', $user->id)
                ->whereIn('status', ['accepted', 'completed'])
                ->where('accepted_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(accepted_at) as date'),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('date')
                ->pluck('revenue', 'date')
                ->toArray();

            $monthsAr = [
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
            ];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $currentDate = now()->startOfMonth()->day($d);
                $dateKey = $currentDate->format('Y-m-d');
                
                if ($locale === 'ar') {
                    $monthName = $monthsAr[$currentDate->month];
                    $label = $d . ' ' . $monthName;
                } else {
                    $monthName = $currentDate->format('F');
                    $label = $d . ' ' . $monthName;
                }

                $weeklyRevenue[] = [
                    'day_name' => $label,
                    'revenue' => (float) ($rawRevenue[$dateKey] ?? 0.0),
                ];
            }
        } else { // weekly
            $startDate = now()->subDays(6)->startOfDay();
            $rawRevenue = Order::where('library_owner_id', $user->id)
                ->whereIn('status', ['accepted', 'completed'])
                ->where('accepted_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(accepted_at) as date'),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('date')
                ->pluck('revenue', 'date')
                ->toArray();

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayName = now()->subDays($i)->format('l'); // Sunday, Monday, etc.
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
