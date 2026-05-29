<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Get books by category
     * جلب الكتب حسب الصنف
     */
    public function getByCategory(Request $request, $categoryId)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $books = Book::with(['category', 'libraryOwner'])
            ->active()
            ->inStock()
            ->byCategory($categoryId)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'books' => BookResource::collection($books),
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
     * Get single book details
     * جلب تفاصيل كتاب واحد
     */
    public function show(Request $request, $id)
    {
        $book = Book::with(['category', 'libraryOwner'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'book' => new BookResource($book),
            ],
        ]);
    }
}
