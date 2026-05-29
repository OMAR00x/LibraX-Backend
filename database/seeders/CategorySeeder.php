<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'روايات',
                'name_en' => 'Novels',
                'is_active' => true,
            ],
            [
                'name_ar' => 'علوم',
                'name_en' => 'Science',
                'is_active' => true,
            ],
            [
                'name_ar' => 'تاريخ',
                'name_en' => 'History',
                'is_active' => true,
            ],
            [
                'name_ar' => 'تكنولوجيا',
                'name_en' => 'Technology',
                'is_active' => true,
            ],
            [
                'name_ar' => 'فلسفة',
                'name_en' => 'Philosophy',
                'is_active' => true,
            ],
            [
                'name_ar' => 'أدب',
                'name_en' => 'Literature',
                'is_active' => true,
            ],
            [
                'name_ar' => 'دين',
                'name_en' => 'Religion',
                'is_active' => true,
            ],
            [
                'name_ar' => 'طبخ',
                'name_en' => 'Cooking',
                'is_active' => true,
            ],
            [
                'name_ar' => 'رياضة',
                'name_en' => 'Sports',
                'is_active' => true,
            ],
            [
                'name_ar' => 'أطفال',
                'name_en' => 'Children',
                'is_active' => true,
            ],
            [
                'name_ar' => 'تنمية بشرية',
                'name_en' => 'Self Development',
                'is_active' => true,
            ],
            [
                'name_ar' => 'اقتصاد',
                'name_en' => 'Economy',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
