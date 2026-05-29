<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $books = Book::with('libraryOwner')->get();

        if ($customers->isEmpty() || $books->isEmpty()) {
            echo "⚠️  No customers or books found. Run DemoDataSeeder first!\n";
            return;
        }

        // طلبات مكتملة مع تقييمات
        foreach ($customers->take(2) as $customer) {
            $book = $books->random();

            $order = Order::create([
                'customer_id' => $customer->id,
                'book_id' => $book->id,
                'library_owner_id' => $book->library_owner_id,
                'price' => $book->price,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'rating' => rand(4, 5),
                'review' => 'كتاب رائع جداً',
                'accepted_at' => now()->subDays(5),
                'completed_at' => now()->subDays(2),
                'created_at' => now()->subDays(7),
            ]);

            // معاملة الشراء
            WalletTransaction::create([
                'user_id' => $customer->id,
                'type' => 'purchase',
                'amount' => $book->price,
                'balance_before' => $customer->wallet_balance + $book->price,
                'balance_after' => $customer->wallet_balance,
                'description' => "شراء كتاب: {$book->title}",
                'order_id' => $order->id,
                'created_at' => $order->created_at,
            ]);

            echo "✅ Completed order created for {$customer->first_name}\n";
        }

        // طلبات قيد المراجعة
        foreach ($customers as $customer) {
            $book = $books->random();

            Order::create([
                'customer_id' => $customer->id,
                'book_id' => $book->id,
                'library_owner_id' => $book->library_owner_id,
                'price' => $book->price,
                'payment_method' => rand(0, 1) ? 'wallet' : 'cash',
                'status' => 'pending',
                'created_at' => now()->subHours(rand(1, 24)),
            ]);

            echo "✅ Pending order created for {$customer->first_name}\n";
        }

        // طلب مرفوض
        $customer = $customers->first();
        $book = $books->random();

        Order::create([
            'customer_id' => $customer->id,
            'book_id' => $book->id,
            'library_owner_id' => $book->library_owner_id,
            'price' => $book->price,
            'payment_method' => 'cash',
            'status' => 'rejected',
            'rejection_reason' => 'الكتاب غير متوفر حالياً',
            'rejected_at' => now()->subDays(1),
            'created_at' => now()->subDays(2),
        ]);

        echo "✅ Rejected order created\n";

        // معاملات شحن للزبائن
        foreach ($customers as $customer) {
            WalletTransaction::create([
                'user_id' => $customer->id,
                'type' => 'charge',
                'amount' => 50000,
                'balance_before' => $customer->wallet_balance - 50000,
                'balance_after' => $customer->wallet_balance,
                'description' => 'شحن رصيد',
                'created_at' => now()->subDays(10),
            ]);

            echo "✅ Wallet transaction created for {$customer->first_name}\n";
        }

        echo "\n✅ Orders and transactions seeded successfully!\n";
    }
}
