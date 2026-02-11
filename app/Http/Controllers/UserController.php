<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function __construct() {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (strtoupper($request->role) === 'AS') {
            $as = User::role('Account Specialist')
                ->pluck('full_name');

            return $this->success('Account specialists fetched', $as, 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show User
     * 
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $userData = new UserResource($user);

        return $this->success('User details fetched successfully', $userData, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
