<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

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

            if (!Auth::attempt($credentials)) {
                return $this->error('Invalid credentials', 401);
            }

            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

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
        $user = $request->user();

        if ($user->tokens()->count() > 0) {
            $user->tokens()->delete();
        }

        return $this->success('Logout successful', 200);
    }
}
