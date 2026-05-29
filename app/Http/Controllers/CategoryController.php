<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all active categories
     * جلب جميع الأصناف النشطة
     */
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['books' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => CategoryResource::collection($categories),
            ],
        ]);
    }

    /**
     * Get single category
     * جلب صنف واحد
     */
    public function show($id)
    {
        $category = Category::withCount(['books' => function ($query) {
            $query->where('is_active', true);
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'category' => new CategoryResource($category),
            ],
        ]);
    }
}
