<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$user ||
            !Hash::check($validated['password'], $user->password)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $token = $user->createToken('pos-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                    'role',
                ]),
                'token' => $token,
            ],
        ]);
    }

    public function storeAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
            'role' => ['required', 'in:admin,cashier'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun admin berhasil dibuat.',
            'data' => [
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                    'role',
                ]),
            ],
        ], 201);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->only([
                    'id',
                    'name',
                    'email',
                    'role',
                ]),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function index()
    {
        $users = \App\Models\User::query()
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'data' => $users,
        ]);
    }


    public function updateUser(Request $request, User $user): JsonResponse
    {
        // Lindungi akun Super Admin dari perubahan melalui panel ini.
        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Super Admin tidak dapat diubah dari panel ini.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'role' => ['required', 'in:admin,cashier'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make(
                $validated['password']
            );
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => [
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                    'role',
                ]),
            ],
        ]);
    }

}
