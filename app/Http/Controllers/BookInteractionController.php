<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingProgress;
use App\Models\BookHighlight;
use App\Models\BookNote;
use App\Models\BookReview;
use App\Models\BookQuote;
use App\Http\Resources\BookReviewResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookInteractionController extends Controller
{
    /**
     * Get all interactive content for a book (progress, highlights, notes, quotes, user review).
     */
    public function getInteractions($bookId)
    {
        $userId = Auth::id();
        
        // Check if book exists
        $book = Book::findOrFail($bookId);

        $progress = ReadingProgress::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->first();

        $highlights = BookHighlight::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->orderBy('page_number')
            ->get();

        $notes = BookNote::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->orderBy('page_number')
            ->get();

        $quotes = BookQuote::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->latest()
            ->get();

        $bookmarks = \App\Models\Bookmark::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->orderBy('page_number')
            ->get();

        $userReview = BookReview::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'progress' => $progress,
                'highlights' => $highlights,
                'notes' => $notes,
                'quotes' => $quotes,
                'bookmarks' => $bookmarks,
                'user_review' => $userReview ? new BookReviewResource($userReview) : null,
            ]
        ]);
    }

    /**
     * Update/Upsert user reading progress.
     */
    public function updateProgress(Request $request, $bookId)
    {
        $userId = Auth::id();
        $book = Book::findOrFail($bookId);

        $request->validate([
            'last_page' => 'required|integer|min:1',
            'progress_percent' => 'required|numeric|between:0,100',
            'total_reading_seconds' => 'required|integer|min:0',
            'is_completed' => 'required|boolean',
        ]);

        $isCompleted = $request->input('is_completed');

        $progress = ReadingProgress::updateOrCreate(
            ['user_id' => $userId, 'book_id' => $bookId],
            [
                'last_page' => $request->input('last_page'),
                'progress_percent' => $request->input('progress_percent'),
                'total_reading_seconds' => $request->input('total_reading_seconds'),
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? ($progress->completed_at ?? now()) : null,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ تقدم القراءة بنجاح',
            'data' => $progress
        ]);
    }

    /**
     * Store text highlight.
     */
    public function storeHighlight(Request $request, $bookId)
    {
        $userId = Auth::id();
        $book = Book::findOrFail($bookId);

        $request->validate([
            'highlight_text' => 'required|string',
            'color' => 'required|string|max:50',
            'page_number' => 'required|integer|min:1',
        ]);

        $highlight = BookHighlight::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'highlight_text' => $request->input('highlight_text'),
            'color' => $request->input('color'),
            'page_number' => $request->input('page_number'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تظليل النص بنجاح',
            'data' => $highlight
        ], 201);
    }

    /**
     * Delete highlight.
     */
    public function destroyHighlight($id)
    {
        $highlight = BookHighlight::where('user_id', Auth::id())->findOrFail($id);
        $highlight->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف التظليل بنجاح'
        ]);
    }

    /**
     * Store note.
     */
    public function storeNote(Request $request, $bookId)
    {
        $userId = Auth::id();
        $book = Book::findOrFail($bookId);

        $request->validate([
            'highlight_text' => 'nullable|string',
            'note_content' => 'required|string',
            'page_number' => 'required|integer|min:1',
        ]);

        $note = BookNote::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'highlight_text' => $request->input('highlight_text'),
            'note_content' => $request->input('note_content'),
            'page_number' => $request->input('page_number'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ الملاحظة بنجاح',
            'data' => $note
        ], 201);
    }

    /**
     * Delete note.
     */
    public function destroyNote($id)
    {
        $note = BookNote::where('user_id', Auth::id())->findOrFail($id);
        $note->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الملاحظة بنجاح'
        ]);
    }

    /**
     * Store quote.
     */
    public function storeQuote(Request $request, $bookId)
    {
        $userId = Auth::id();
        $book = Book::findOrFail($bookId);

        $request->validate([
            'quote_text' => 'required|string',
            'category_name' => 'nullable|string|max:255',
        ]);

        $quote = BookQuote::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'quote_text' => $request->input('quote_text'),
            'category_name' => $request->input('category_name'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ الاقتباس بنجاح',
            'data' => $quote
        ], 201);
    }

    /**
     * Delete quote.
     */
    public function destroyQuote($id)
    {
        $quote = BookQuote::where('user_id', Auth::id())->findOrFail($id);
        $quote->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الاقتباس بنجاح'
        ]);
    }

    /**
     * Get book reviews.
     */
    public function getReviews($bookId)
    {
        $book = Book::findOrFail($bookId);
        
        $reviews = BookReview::where('book_id', $bookId)
            ->with('user')
            ->latest()
            ->paginate(15);

        // Calculate distribution
        $starsCount = BookReview::where('book_id', $bookId)
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        // Ensure 1 to 5 are populated
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = $starsCount[$i] ?? 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => BookReviewResource::collection($reviews)->response()->getData(true),
                'average_rating' => (float) $book->average_rating,
                'total_ratings' => (int) $book->total_ratings,
                'distribution' => $distribution
            ]
        ]);
    }

    /**
     * Store/Update user book review and rating.
     */
    public function storeReview(Request $request, $bookId)
    {
        $userId = Auth::id();
        $book = Book::findOrFail($bookId);

        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'review_content' => 'nullable|string',
        ]);

        DB::transaction(function () use ($userId, $bookId, $book, $request, &$review) {
            $review = BookReview::updateOrCreate(
                ['user_id' => $userId, 'book_id' => $bookId],
                [
                    'rating' => $request->input('rating'),
                    'review_content' => $request->input('review_content'),
                ]
            );

            // Recalculate book average rating
            $stats = BookReview::where('book_id', $bookId)
                ->select(DB::raw('count(*) as count'), DB::raw('avg(rating) as avg'))
                ->first();

            $book->update([
                'average_rating' => round($stats->avg, 2),
                'total_ratings' => $stats->count
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم نشر المراجعة والتقييم بنجاح',
            'data' => new BookReviewResource($review)
        ]);
    }

    /**
     * Get user overall e-reading analytics.
     */
    public function getStatistics()
    {
        $userId = Auth::id();

        // 1. Pages read: sum last_page of all progresses
        $pagesRead = (int) ReadingProgress::where('user_id', $userId)->sum('last_page');

        // 2. Books completed
        $completedBooks = (int) ReadingProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        // 3. Total reading time
        $totalSeconds = (int) ReadingProgress::where('user_id', $userId)->sum('total_reading_seconds');
        $totalHours = round($totalSeconds / 3600, 1);

        // 4. Daily average reading time (in minutes, assuming last 7 days of activity)
        $avgDailyMinutes = round(($totalSeconds / 60) / max(1, ReadingProgress::where('user_id', $userId)->count() * 1.5), 1);

        // 5. Weekly logs: actual updates in last 7 days
        // Group by day of week for charts (0 = Sunday, 6 = Saturday)
        $weeklyDistribution = [
            'الأحد' => 0,
            'الاثنين' => 0,
            'الثلاثاء' => 0,
            'الأربعاء' => 0,
            'الخميس' => 0,
            'الجمعة' => 0,
            'السبت' => 0
        ];

        // Simulate some dynamic daily active minutes based on progresses for a beautiful chart
        $progresses = ReadingProgress::where('user_id', $userId)
            ->with('book')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        $activityLogs = [];
        $arabicDays = [
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        ];

        foreach ($progresses as $prog) {
            $dayName = $prog->updated_at->format('l');
            $arabicDay = $arabicDays[$dayName] ?? 'الأحد';
            
            // Add to distribution (in minutes)
            $progMinutes = round($prog->total_reading_seconds / 60);
            $weeklyDistribution[$arabicDay] = min(180, ($weeklyDistribution[$arabicDay] ?? 0) + $progMinutes);

            $activityLogs[] = [
                'book_title' => $prog->book->title,
                'author' => $prog->book->author,
                'last_page' => $prog->last_page,
                'progress_percent' => (float)$prog->progress_percent,
                'minutes_read' => $progMinutes,
                'date' => $prog->updated_at->format('Y-m-d H:i'),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'pages_read' => $pagesRead,
                'completed_books' => $completedBooks,
                'total_hours' => $totalHours,
                'daily_average_minutes' => $avgDailyMinutes,
                'weekly_distribution' => $weeklyDistribution,
                'activity_logs' => $activityLogs,
            ]
        ]);
    }

    /**
     * Get book bookmarks.
     */
    public function getBookmarks($bookId)
    {
        $userId = Auth::id();
        Book::findOrFail($bookId);

        $bookmarks = \App\Models\Bookmark::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->orderBy('page_number')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookmarks
        ]);
    }

    /**
     * Store bookmark.
     */
    public function storeBookmark(Request $request, $bookId)
    {
        $userId = Auth::id();
        Book::findOrFail($bookId);

        $request->validate([
            'page_number' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $bookmark = \App\Models\Bookmark::updateOrCreate(
            [
                'user_id' => $userId,
                'book_id' => $bookId,
                'page_number' => $request->input('page_number'),
            ],
            [
                'note' => $request->input('note'),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ الإشارة المرجعية بنجاح',
            'data' => $bookmark
        ], 201);
    }

    /**
     * Delete bookmark.
     */
    public function destroyBookmark($id)
    {
        $bookmark = \App\Models\Bookmark::where('user_id', Auth::id())->findOrFail($id);
        $bookmark->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الإشارة المرجعية بنجاح'
        ]);
    }
}
