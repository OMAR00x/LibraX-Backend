<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Get all users
     * جميع المستخدمين
     */
    public function index(Request $request)
    {
        $request->validate([
            'role' => 'nullable|in:admin,library_owner,customer',
            'search' => 'nullable|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('library_name', 'like', "%{$request->search}%");
            });
        }

        $users = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
                'counts' => [
                    'total' => User::count(),
                    'customers' => User::where('role', 'customer')->count(),
                    'library_owners' => User::where('role', 'library_owner')->count(),
                    'admins' => User::where('role', 'admin')->count(),
                ],
            ],
        ]);
    }

    /**
     * Get single user
     * تفاصيل مستخدم واحد
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Create new user
     * إضافة مستخدم جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,library_owner,customer',
            'library_name' => 'required_if:role,library_owner|string|max:255',
            'library_address' => 'nullable|string|max:500',
            'library_latitude' => 'nullable|numeric',
            'library_longitude' => 'nullable|numeric',
            'wallet_balance' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'library_name' => $request->library_name,
            'library_address' => $request->library_address,
            'library_latitude' => $request->library_latitude,
            'library_longitude' => $request->library_longitude,
            'wallet_balance' => $request->wallet_balance ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة المستخدم بنجاح',
            'message_en' => 'User created successfully',
            'data' => [
                'user' => new UserResource($user),
            ],
        ], 201);
    }

    /**
     * Update user
     * تعديل المستخدم
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|unique:users,phone,' . $id,
            'password' => 'nullable|string|min:6',
            'library_name' => 'nullable|string|max:255',
            'library_address' => 'nullable|string|max:500',
            'library_latitude' => 'nullable|numeric',
            'library_longitude' => 'nullable|numeric',
            'wallet_balance' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'first_name',
            'last_name',
            'phone',
            'library_name',
            'library_address',
            'library_latitude',
            'library_longitude',
            'is_active',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث المستخدم بنجاح',
            'message_en' => 'User updated successfully',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Delete user
     * حذف المستخدم
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // منع حذف الأدمن الحالي
        if ($user->id === auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكنك حذف حسابك الخاص',
                'message_en' => 'Cannot delete your own account',
            ], 400);
        }

        // التحقق من الرصيد والطلبات للزبائن
        if ($user->role === 'customer') {
            if ($user->wallet_balance > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن حذف زبون لديه رصيد في المحفظة',
                    'message_en' => 'Cannot delete customer with wallet balance',
                ], 400);
            }

            $hasPendingOrders = $user->orders()->whereIn('status', ['pending', 'accepted'])->exists();
            if ($hasPendingOrders) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن حذف زبون لديه طلبات نشطة',
                    'message_en' => 'Cannot delete customer with active orders',
                ], 400);
            }
        }

        // التحقق من الكتب لأصحاب المكتبات
        if ($user->role === 'library_owner') {
            $hasBooks = $user->books()->exists();
            if ($hasBooks) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن حذف صاحب مكتبة لديه كتب. يرجى حذف الكتب أولاً',
                    'message_en' => 'Cannot delete library owner with books',
                ], 400);
            }

            $hasPendingOrders = $user->libraryOrders()->whereIn('status', ['pending', 'accepted'])->exists();
            if ($hasPendingOrders) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن حذف صاحب مكتبة لديه طلبات نشطة',
                    'message_en' => 'Cannot delete library owner with active orders',
                ], 400);
            }
        }

        // Soft delete
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف المستخدم بنجاح',
            'message_en' => 'User deleted successfully',
        ]);
    }
}
