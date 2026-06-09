<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookListResource;
use App\Http\Resources\CategoryResource;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get home page data
     * يجلب بيانات الصفحة الرئيسية: اسم المستخدم، عدد الإشعارات، الأصناف
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        $userData = null;
        $unreadNotificationsCount = 0;
        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
            ];
            $unreadNotificationsCount = $user->unreadNotifications()->count();
        }

        // جلب الأصناف النشطة مع عدد الكتب (التي تحتوي على كتب نشطة فقط)
        $categories = Category::where('is_active', true)
            ->withCount(['books' => function ($query) {
                $query->where('is_active', true);
            }])
            ->having('books_count', '>', 0)
            ->get();

        // جلب أحدث الكتب (للعرض الافتراضي)
        $latestBooks = Book::with(['category', 'libraryOwner'])
            ->active()
            ->inStock()
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $userData,
                'unread_notifications_count' => $unreadNotificationsCount,
                'categories' => CategoryResource::collection($categories),
                'latest_books' => BookListResource::collection($latestBooks),
            ],
        ]);
    }

    /**
     * Search books
     * البحث عن الكتب
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'category_id' => 'nullable|exists:categories,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Book::with(['category', 'libraryOwner'])
            ->active()
            ->search($request->query);

        if ($request->category_id) {
            $query->byCategory($request->category_id);
        }

        $books = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'books' => BookListResource::collection($books),
                'pagination' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total(),
                ],
            ],
        ]);
    }

    /**
     * Filter books
     * فلترة الكتب حسب السعر، التقييم، الصنف، الترتيب
     */
    public function filter(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'sort_by' => 'nullable|in:price,average_rating,created_at,total_sales',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Book::with(['category', 'libraryOwner'])
            ->active()
            ->inStock();

        // تطبيق الفلاتر
        if ($request->category_id) {
            $query->byCategory($request->category_id);
        }

        $query->filterByPrice($request->min_price, $request->max_price);
        $query->filterByRating($request->min_rating);

        // الترتيب
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->sortBy($sortBy, $sortOrder);

        $books = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'books' => BookListResource::collection($books),
                'pagination' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total(),
                ],
                'filters_applied' => [
                    'category_id' => $request->category_id,
                    'min_price' => $request->min_price,
                    'max_price' => $request->max_price,
                    'min_rating' => $request->min_rating,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ],
        ]);
    }
}
