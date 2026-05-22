<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{

    /**
     * Register User
     * 
     * Register a new user
     */
    // public function register (Request $request) {
    //     $validated = $request->validate([
    //         'email' => 'required',
    //         'password' => 'required'
    //     ]);
    //     if ($validated) {
    //         DB::beginTransaction();
    //         try {
    //             $newUser = User::create([
    //                 'first_name' => $request->firstName,
    //                 'last_name' => $request->lastName,
    //                 'address' => $request->address,
    //                 'contact_number' => $request->contactNumber,
    //                 'email' => $request->email,
    //                 'password' => Hash::make($request->password),
    //                 'password_length' => strlen($request->password)
    //             ]);
    //             DB::commit();
    //             return $this->success('User registered', $newUser, 200);
    //         } catch(\Exception $e) {
    //             DB::rollback();
    //             return $this->error('Something went wrong', 400, $e);
    //         }
    //     }
    // }

    /**
     * Login
     * 
     * Login to registered account
     */
    public function login(Request $request)
    {
        $platform = strtolower($request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web' || $request->hasHeader('X-XSRF-TOKEN');

        $key = Str::lower($request->email).'|'.$request->ip();
        $decay = min(60 * pow(2, RateLimiter::attempts($key)), 3600);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many login attempts. Try again later.',
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        $validated = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validated) {
            $login = trim($validated['email']);
            $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            $credentials = [
                $loginField => $login,
                'password' => $validated['password'],
            ];

            if (!Auth::attempt($credentials, $request->boolean('remember'))) {
                RateLimiter::hit($key, $decay);

                return $this->error('Invalid credentials', 401);
            }

            $user = auth()->user();

            if ($isWeb) {
                if ($request->hasSession()) {
                    $request->session()->regenerate();
                }

                RateLimiter::clear($key);

                return $this->success('Logged in successfully', ['user' => new UserResource($user)]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            RateLimiter::clear($key);

            return $this->success('Logged in successfully', ['user' => new UserResource($user), 'token' => $token]);
        }
    }

    /**
     * Logout
     * 
     * Logout of authenticated account
     */
    public function logout(Request $request)
    {
        $platform = strtolower($request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web' || $request->hasHeader('X-XSRF-TOKEN');
        
        if ($isWeb) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $this->success('Logout successful');
        }

        $user = $request->user();

        if ($user->tokens()->count() > 0) {
            $user->tokens()->delete();
        }

        return $this->success('Logout successful', 200);
    }
}
