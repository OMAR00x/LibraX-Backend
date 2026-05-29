<?php

namespace App\Http\Controllers\LibraryOwner;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LibraryBookController extends Controller
{
    /**
     * Get library owner's books
     * كتب المكتبة
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'search' => 'nullable|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Book::with(['category'])
            ->where('library_owner_id', $user->id);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
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
                    'total_books' => Book::where('library_owner_id', $user->id)->count(),
                    'active_books' => Book::where('library_owner_id', $user->id)->where('is_active', true)->count(),
                    'total_copies' => Book::where('library_owner_id', $user->id)->sum('quantity'),
                ],
            ],
        ]);
    }

    /**
     * Get single book
     * تفاصيل كتاب واحد
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $book = Book::with(['category'])
            ->where('library_owner_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'book' => new BookResource($book),
            ],
        ]);
    }

    /**
     * Create new book
     * إضافة كتاب جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pdf_file' => 'nullable|mimes:pdf|max:10240',
            'audio_file' => 'nullable|mimes:mp3,wav|max:20480',
            'is_active' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // رفع الملفات
        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('books/covers', 'public');
        }

        $pdfPath = null;
        if ($request->hasFile('pdf_file')) {
            $pdfPath = $request->file('pdf_file')->store('books/pdfs', 'public');
        }

        $audioPath = null;
        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('books/audios', 'public');
        }

        $book = Book::create([
            'library_owner_id' => $user->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'author' => $request->author,
            'description' => $request->description,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'cover_image' => $coverPath,
            'pdf_file' => $pdfPath,
            'audio_file' => $audioPath,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة الكتاب بنجاح',
            'message_en' => 'Book added successfully',
            'data' => [
                'book' => new BookResource($book->load('category')),
            ],
        ], 201);
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pdf_file' => 'nullable|mimes:pdf|max:10240',
            'audio_file' => 'nullable|mimes:mp3,wav|max:20480',
            'is_active' => 'nullable|boolean',
        ]);

        $user = $request->user();

        $book = Book::where('library_owner_id', $user->id)->findOrFail($id);

        // تحديث الملفات إذا تم رفع ملفات جديدة
        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $book->cover_image = $request->file('cover_image')->store('books/covers', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            if ($book->pdf_file) {
                Storage::disk('public')->delete($book->pdf_file);
            }
            $book->pdf_file = $request->file('pdf_file')->store('books/pdfs', 'public');
        }

        if ($request->hasFile('audio_file')) {
            if ($book->audio_file) {
                Storage::disk('public')->delete($book->audio_file);
            }
            $book->audio_file = $request->file('audio_file')->store('books/audios', 'public');
        }

        // تحديث البيانات الأخرى
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
                'book' => new BookResource($book->load('category')),
            ],
        ]);
    }

    /**
     * Delete book
     * حذف الكتاب
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $book = Book::where('library_owner_id', $user->id)->findOrFail($id);

        // حذف الملفات
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        if ($book->pdf_file) {
            Storage::disk('public')->delete($book->pdf_file);
        }
        if ($book->audio_file) {
            Storage::disk('public')->delete($book->audio_file);
        }

        $book->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الكتاب بنجاح',
            'message_en' => 'Book deleted successfully',
        ]);
    }
}
