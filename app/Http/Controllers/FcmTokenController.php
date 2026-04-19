<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FcmToken;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();

        FcmToken::updateOrCreate(
            ['user_id' => $user->id, 'token' => $request->token],
        );

        return response()->json(['message' => 'تم حفظ رمز الإشعارات بنجاح']);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        FcmToken::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'تم حذف رمز الإشعارات']);
    }
}
