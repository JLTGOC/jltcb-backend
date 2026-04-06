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
use App\Models\{
    User,
    Shipment
};
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
        //
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

                if ($user->company_name === 'JLTCB') {
                    $user->company_position = $data['position'];
                    $user->save();
                }
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

    /**
     * Index Account Specialists
     * 
     * Display a listing of account specialists.
     */
    public function indexAccountSpecialists()
    {
        $this->authorize('indexAccountSpecialists', User::class);

        $accountSpecialists = User::role('Account Specialist')->get();
        $accountSpecialists = $accountSpecialists->map(function ($s) {
            return [
                'id' => $s->id,
                'full_name' => $s->full_name,
            ];
        });

        return $this->success('Account specialists fetched successfully', $accountSpecialists, 200);
    }

    public function indexClientAccounts(Request $request) {
        $this->authorize('indexClientAccounts', User::class);

        $user = auth()->user();

        $clientIds = User::role('Client')->pluck('id');
        if ($user->hasRole('Account Specialist')) {
            $asShipments = Shipment::where('as_id', $user->id)->distinct('client_id')->pluck('client_id');
        } elseif ($user->hasRole('Lead Account Specialist')) {
            $asShipments = Shipment::distinct('client_id')->pluck('client_id');
        }

        $query = User::query()
            ->role('Client')
            ->whereIn('id', $clientIds)
            ->whereIn('id', $asShipments);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->input('search');
            $query = $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('id', 'LIKE', '%' . $search . '%');
            });
        }
        
        $clients = $query->get()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'full_name' => $c->full_name,
                    'ongoing_shipments' => Shipment::where('client_id', $c->id)->whereIn('status', ['PENDING', 'NOT YET DELIVERED', 'IN TRANSIT', 'ARRIVED', 'BERTHED', 'DISCHARGED'])->count(),
                    'completed_shipments' => Shipment::where('client_id', $c->id)->where('status', 'DELIVERED')->count(),
                ];
            });

        return $this->success('Clients fetched successfully', $clients, 200);
    }

    public function indexClientShipments(Request $request, User $client) {
        $clientShipments = Shipment::where('client_id', $client->id);

        if ($request->has('search')) {
            $search = $request->input('search');
            $clientShipments = $clientShipments->where('reference_number', 'LIKE', '%' . $search . '%');
        }
        
        $clientShipments = $clientShipments->get()->map(function ($s) {
            return [
                'reference_number' => $s->reference_number,
                'commodity' => $s->commodity,
                'status' => $s->status,
            ];
        });

        return $this->success('Client shipments fetched successfully', [
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
            ],
            'shipments' => $clientShipments
        ], 200);
    }
}
