<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminBookController extends Controller
{
    /**
     * Get all books
     * جميع الكتب
     */
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'library_owner_id' => 'nullable|exists:users,id',
            'search' => 'nullable|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Book::with(['category', 'libraryOwner']);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->library_owner_id) {
            $query->where('library_owner_id', $request->library_owner_id);
        }

        if ($request->search) {
            $query->search($request->search);
        }

        $books = $query->latest()->paginate($request->per_page ?? 20);

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
                'summary' => [
                    'total_books' => Book::count(),
                    'active_books' => Book::where('is_active', true)->count(),
                    'total_copies' => Book::sum('quantity'),
                ],
            ],
        ]);
    }

    /**
     * Get single book
     * تفاصيل كتاب واحد
     */
    public function show($id)
    {
        $book = Book::with(['category', 'libraryOwner'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'book' => new BookResource($book),
            ],
        ]);
    }

    /**
     * Update book
     * تعديل الكتاب
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'title' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $book = Book::findOrFail($id);

        $book->update($request->only([
            'category_id',
            'title',
            'author',
            'description',
            'price',
            'quantity',
            'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الكتاب بنجاح',
            'message_en' => 'Book updated successfully',
            'data' => [
                'book' => new BookResource($book->load(['category', 'libraryOwner'])),
            ],
        ]);
    }

    /**
     * Delete book
     * حذف الكتاب
     */
    public function destroy($id)
    {
        $book = Book::findOrFail($id);

        // التحقق من وجود طلبات نشطة
        $hasActiveOrders = Order::where('book_id', $id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($hasActiveOrders) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن حذف الكتاب لوجود طلبات نشطة عليه',
                'message_en' => 'Cannot delete book with active orders',
            ], 409);
        }

        DB::beginTransaction();
        try {
            // حذف من قاعدة البيانات
            $book->delete();

            DB::commit();

            // حذف الملفات بعد نجاح الـ commit
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            if ($book->pdf_file) {
                Storage::disk('public')->delete($book->pdf_file);
            }
            if ($book->audio_file) {
                Storage::disk('public')->delete($book->audio_file);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف الكتاب بنجاح',
                'message_en' => 'Book deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء حذف الكتاب',
                'message_en' => 'Error deleting book',
            ], 500);
        }
    }
}
