<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reading Progresses Table
        Schema::create('reading_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('last_page')->default(1);
            $table->decimal('progress_percent', 5, 2)->default(0.00);
            $table->unsignedInteger('total_reading_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
        });

        // 2. Book Highlights Table
        Schema::create('book_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->text('highlight_text');
            $table->string('color', 50)->default('yellow'); // e.g. yellow, green, blue, pink, orange
            $table->unsignedInteger('page_number');
            $table->timestamps();
        });

        // 3. Book Notes Table
        Schema::create('book_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->text('highlight_text')->nullable();
            $table->text('note_content');
            $table->unsignedInteger('page_number');
            $table->timestamps();
        });

        // 4. Book Reviews & Ratings Table
        Schema::create('book_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); // 1 to 5 stars
            $table->text('review_content')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
        });

        // 5. Book Quotes Table
        Schema::create('book_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->text('quote_text');
            $table->string('category_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_quotes');
        Schema::dropIfExists('book_reviews');
        Schema::dropIfExists('book_notes');
        Schema::dropIfExists('book_highlights');
        Schema::dropIfExists('reading_progresses');
    }
};
