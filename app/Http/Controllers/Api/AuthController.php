<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Đổi email + mật khẩu lấy Sanctum token để gọi các API còn lại.
     */
    public function login(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return response()->json(['message' => 'Email and password are required.'], 422);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $deviceName = trim((string) $request->input('device_name', '')) ?: 'api';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json(['data' => [
            'token' => $token,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json(['data' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
        ]]);
    }

    /** Thu hồi token đang dùng. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
