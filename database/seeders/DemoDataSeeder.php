<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo data for testing
     */
    public function run(): void
    {
        // 1. Create Admin
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'System',
            'phone' => '0911111111',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        echo "✅ Admin created: {$admin->phone}\n";

        // 2. Create Library Owners
        $libraryOwners = [
            [
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
                'phone' => '0923456789',
                'password' => bcrypt('password'),
                'role' => 'library_owner',
                'library_name' => 'مكتبة النور',
                'library_address' => 'دمشق - المزة',
                'library_latitude' => 33.5138,
                'library_longitude' => 36.2765,
                'wallet_balance' => 500,
                'is_active' => true,
            ],
            [
                'first_name' => 'أحمد',
                'last_name' => 'خالد',
                'phone' => '0933333333',
                'password' => bcrypt('password'),
                'role' => 'library_owner',
                'library_name' => 'مكتبة الحكمة',
                'library_address' => 'دمشق - أبو رمانة',
                'library_latitude' => 33.5024,
                'library_longitude' => 36.2989,
                'wallet_balance' => 750,
                'is_active' => true,
            ],
            [
                'first_name' => 'سارة',
                'last_name' => 'محمود',
                'phone' => '0944444444',
                'password' => bcrypt('password'),
                'role' => 'library_owner',
                'library_name' => 'مكتبة المعرفة',
                'library_address' => 'حلب - العزيزية',
                'library_latitude' => 36.2021,
                'library_longitude' => 37.1343,
                'wallet_balance' => 300,
                'is_active' => true,
            ],
        ];

        foreach ($libraryOwners as $owner) {
            $user = User::create($owner);
            echo "✅ Library Owner created: {$user->library_name} ({$user->phone})\n";
        }

        // 3. Create Customers
        $customers = [
            [
                'first_name' => 'علي',
                'last_name' => 'خالد',
                'phone' => '0934567890',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'wallet_balance' => 100,
                'is_active' => true,
            ],
            [
                'first_name' => 'فاطمة',
                'last_name' => 'حسن',
                'phone' => '0955555555',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'wallet_balance' => 200,
                'is_active' => true,
            ],
            [
                'first_name' => 'يوسف',
                'last_name' => 'عمر',
                'phone' => '0966666666',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'wallet_balance' => 150,
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            $user = User::create($customer);
            echo "✅ Customer created: {$user->first_name} {$user->last_name} ({$user->phone})\n";
        }

        // 4. Get categories
        $categories = Category::all();
        if ($categories->isEmpty()) {
            echo "⚠️  No categories found. Run CategorySeeder first!\n";
            return;
        }

        // 5. Create Books
        $books = [
            // مكتبة النور
            [
                'library_owner_id' => 2,
                'category_id' => 1, // روايات
                'title' => 'الخيميائي',
                'author' => 'باولو كويلو',
                'description' => 'رواية عن رحلة راعي أندلسي يبحث عن كنز في مصر، ويكتشف أن الكنز الحقيقي في داخله',
                'price' => 15.50,
                'quantity' => 10,
                'average_rating' => 4.5,
                'total_ratings' => 120,
                'total_sales' => 85,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 2,
                'category_id' => 2, // علوم
                'title' => 'موجز تاريخ الزمن',
                'author' => 'ستيفن هوكينج',
                'description' => 'كتاب علمي يشرح الكون والزمن والثقوب السوداء بطريقة مبسطة',
                'price' => 25.00,
                'quantity' => 5,
                'average_rating' => 4.8,
                'total_ratings' => 200,
                'total_sales' => 150,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 2,
                'category_id' => 11, // تنمية بشرية
                'title' => 'العادات السبع للناس الأكثر فعالية',
                'author' => 'ستيفن كوفي',
                'description' => 'كتاب عن تطوير الذات وبناء العادات الإيجابية',
                'price' => 18.00,
                'quantity' => 15,
                'average_rating' => 4.6,
                'total_ratings' => 95,
                'total_sales' => 120,
                'is_active' => true,
            ],

            // مكتبة الحكمة
            [
                'library_owner_id' => 3,
                'category_id' => 3, // تاريخ
                'title' => 'قصة الحضارة',
                'author' => 'ويل ديورانت',
                'description' => 'موسوعة تاريخية شاملة عن تاريخ الحضارات الإنسانية',
                'price' => 45.00,
                'quantity' => 3,
                'average_rating' => 4.9,
                'total_ratings' => 180,
                'total_sales' => 65,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 3,
                'category_id' => 5, // فلسفة
                'title' => 'عالم صوفي',
                'author' => 'جوستاين غاردر',
                'description' => 'رواية فلسفية تأخذك في رحلة عبر تاريخ الفلسفة',
                'price' => 20.00,
                'quantity' => 8,
                'average_rating' => 4.7,
                'total_ratings' => 145,
                'total_sales' => 98,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 3,
                'category_id' => 4, // تكنولوجيا
                'title' => 'الذكاء الاصطناعي',
                'author' => 'ستيوارت راسل',
                'description' => 'مقدمة شاملة عن الذكاء الاصطناعي وتطبيقاته',
                'price' => 35.00,
                'quantity' => 6,
                'average_rating' => 4.4,
                'total_ratings' => 78,
                'total_sales' => 45,
                'is_active' => true,
            ],

            // مكتبة المعرفة
            [
                'library_owner_id' => 4,
                'category_id' => 6, // أدب
                'title' => 'مئة عام من العزلة',
                'author' => 'غابرييل غارسيا ماركيز',
                'description' => 'رواية أدبية عن عائلة بوينديا عبر أجيال',
                'price' => 22.00,
                'quantity' => 12,
                'average_rating' => 4.8,
                'total_ratings' => 210,
                'total_sales' => 175,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 4,
                'category_id' => 10, // أطفال
                'title' => 'الأمير الصغير',
                'author' => 'أنطوان دو سانت إكزوبيري',
                'description' => 'قصة فلسفية للأطفال والكبار عن أمير صغير يسافر بين الكواكب',
                'price' => 12.00,
                'quantity' => 20,
                'average_rating' => 4.9,
                'total_ratings' => 320,
                'total_sales' => 280,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 4,
                'category_id' => 12, // اقتصاد
                'title' => 'الاقتصاد العجيب',
                'author' => 'ستيفن ليفيت',
                'description' => 'نظرة غير تقليدية على الاقتصاد والحياة اليومية',
                'price' => 28.00,
                'quantity' => 7,
                'average_rating' => 4.3,
                'total_ratings' => 92,
                'total_sales' => 56,
                'is_active' => true,
            ],
            [
                'library_owner_id' => 4,
                'category_id' => 1, // روايات
                'title' => '1984',
                'author' => 'جورج أورويل',
                'description' => 'رواية ديستوبية عن مجتمع شمولي يراقب كل شيء',
                'price' => 16.00,
                'quantity' => 9,
                'average_rating' => 4.7,
                'total_ratings' => 165,
                'total_sales' => 132,
                'is_active' => true,
            ],
        ];

        foreach ($books as $bookData) {
            $book = Book::create($bookData);
            echo "✅ Book created: {$book->title} - {$book->libraryOwner->library_name}\n";
        }

        echo "\n";
        echo "========================================\n";
        echo "✅ Demo data seeded successfully!\n";
        echo "========================================\n";
        echo "\n";
        echo "📝 Test Accounts:\n";
        echo "  Admin: 0911111111 / admin123\n";
        echo "  Library Owner 1: 0923456789 / password (مكتبة النور)\n";
        echo "  Library Owner 2: 0933333333 / password (مكتبة الحكمة)\n";
        echo "  Library Owner 3: 0944444444 / password (مكتبة المعرفة)\n";
        echo "  Customer 1: 0934567890 / password\n";
        echo "  Customer 2: 0955555555 / password\n";
        echo "  Customer 3: 0966666666 / password\n";
        echo "\n";
        echo "📚 Books created: " . count($books) . "\n";
        echo "📂 Categories: " . $categories->count() . "\n";
    }
}
