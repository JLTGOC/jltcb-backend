<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthController extends Controller
{
    public function register (Request $request) {
        DB::beginTransaction();
        try {
            $newUser = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            DB::commit();
            return $this->success('User registered', $newUser, 200);
        } catch(\Exception $e) {
            DB::rollback();
            return $this->error('Something went wrong', 400, $e);
        }
        
    }

    public function login (Request $request) {
        $credentials = $request->only('email', 'password');
        
        if (!auth()->attempt($credentials)) {
            return $this->error('Invalid credentials', 400);
        }

        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return $this->success('Logged in successfully', ['user' => $user, 'token' => $token]);
    }

    public function logout (Request $request) {
        $user = $request->user();

        if ($user->tokens()->count() > 0) {
            $user->tokens()->delete();
        }

        return $this->success('Logout successful', 200);
    }
}
