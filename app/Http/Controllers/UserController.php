<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserImageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function __construct() {
        $this->authorizeResource(User::class, 'user');
        $this->middleware('can:changePassword,user')->only('changePassword');
        $this->middleware('can:changeProfile,user')->only('changeProfile');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $as = User::role('Account Specialist')
            ->pluck('full_name');

        return $this->success('Account specialists fetched', $as, 200);
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
     * Update User
     * 
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update(Arr::except($data, ['position']));

            if (array_key_exists('position', $data)) {
                $user->syncRoles($data['position']);
            }
        });

        return $this->success('User updated successfully', new UserResource($user->fresh()), 200);
    }

    /**
     * Change User's Password
     * 
     * Update the password of the specified resource in storage.
     */
    public function changePassword(UpdatePasswordRequest $request, User $user)
    {
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->password = Hash::make($data['new_password']);
            $user->save();
        });

        return $this->success('Password updated successfully', null, 200);
    }

    /**
     * Change Profile Image.
     * 
     * Change the profile image of the specified resource in storage.
     */
    public function changeProfile(UpdateUserImageRequest $request, User $user)
    {
        return DB::transaction(function () use ($request, $user) {
            $isReplacingImage = $request->hasFile('image') || $request->filled('image');

            if ($isReplacingImage) {
                $newImagePath = upload_image($request, 'image', 'users');

                if (! $newImagePath) {
                    throw ValidationException::withMessages([
                        'image' => ['The image is not valid.'],
                    ]);
                }

                if ($user->image_path && Storage::disk('public')->exists($user->image_path)) {
                    Storage::disk('public')->delete($user->image_path);
                }

                $user->image_path = $newImagePath;
            }

            $user->save();

            return $this->success('User image updated successfully', new UserResource($user->fresh()), 200);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
