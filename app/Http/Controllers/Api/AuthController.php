<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', (string) $request->string('email'))->first();
        if (! $user || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => ['Email atau password tidak valid.']]);
        }
        $user->tokens()->delete();
        return response()->json(['token' => $user->createToken('claviar-admin')->plainTextToken, 'user' => $this->userPayload($user)]);
    }

    public function me(Request $request): JsonResponse { return response()->json(['user' => $this->userPayload($request->user())]); }
    public function logout(Request $request): JsonResponse { $request->user()->currentAccessToken()?->delete(); return response()->json(['message' => 'Berhasil logout.']); }

    public function profile(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email,'.$request->user()->id]]);
        $request->user()->update($data);
        return response()->json(['user' => $this->userPayload($request->user()->fresh())]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(8)]]);
        $request->user()->update(['password' => $data['password']]);
        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    private function userPayload(User $user): array { return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'roles' => $user->getRoleNames(), 'permissions' => $user->getAllPermissions()->pluck('name')]; }
}
