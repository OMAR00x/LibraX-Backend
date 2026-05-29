<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookListResource;
use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get user's favorite books
     * جلب الكتب المفضلة للمستخدم
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['book.category', 'book.libraryOwner'])
            ->latest()
            ->paginate(20);

        $books = $favorites->map(function ($favorite) {
            return $favorite->book;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'favorites' => BookListResource::collection($books),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'per_page' => $favorites->perPage(),
                    'total' => $favorites->total(),
                ],
            ],
        ]);
    }

    /**
     * Add book to favorites
     * إضافة كتاب للمفضلة
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $user = $request->user();

        // التحقق من أن الكتاب موجود ونشط
        $book = Book::where('id', $request->book_id)
            ->where('is_active', true)
            ->firstOrFail();

        // التحقق من عدم وجود الكتاب في المفضلة مسبقاً
        $exists = Favorite::where('user_id', $user->id)
            ->where('book_id', $request->book_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'الكتاب موجود في المفضلة مسبقاً',
                'message_en' => 'Book already in favorites',
            ], 400);
        }

        // إضافة للمفضلة
        Favorite::create([
            'user_id' => $user->id,
            'book_id' => $request->book_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إضافة الكتاب للمفضلة بنجاح',
            'message_en' => 'Book added to favorites successfully',
        ], 201);
    }

    /**
     * Remove book from favorites
     * إزالة كتاب من المفضلة
     */
    public function destroy(Request $request, $bookId)
    {
        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'status' => 'error',
                'message' => 'الكتاب غير موجود في المفضلة',
                'message_en' => 'Book not found in favorites',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إزالة الكتاب من المفضلة بنجاح',
            'message_en' => 'Book removed from favorites successfully',
        ]);
    }

    /**
     * Toggle favorite status
     * تبديل حالة المفضلة (إضافة/إزالة)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $user = $request->user();

        // التحقق من أن الكتاب موجود ونشط
        $book = Book::where('id', $request->book_id)
            ->where('is_active', true)
            ->firstOrFail();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('book_id', $request->book_id)
            ->first();

        if ($favorite) {
            // إزالة من المفضلة
            $favorite->delete();
            return response()->json([
                'status' => 'success',
                'action' => 'removed',
                'message' => 'تمت إزالة الكتاب من المفضلة',
                'message_en' => 'Book removed from favorites',
            ]);
        } else {
            // إضافة للمفضلة
            Favorite::create([
                'user_id' => $user->id,
                'book_id' => $request->book_id,
            ]);
            return response()->json([
                'status' => 'success',
                'action' => 'added',
                'message' => 'تمت إضافة الكتاب للمفضلة',
                'message_en' => 'Book added to favorites',
            ]);
        }
    }
}
