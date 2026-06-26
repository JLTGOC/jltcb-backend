<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PersonInChargeService {

    /**
     * @param RoleType[] $roles
     */
    public function getPersonsInCharge(array $roles, ?string $search = null) {
        $users = User::query()->role($roles)
            ->when($search, fn ($query) =>
                $query->where('full_name', 'like', "%{$search}%")
            )
            ->select('id', 'full_name', 'image_path')
            ->orderBy('full_name')
            ->get();

        return $users->map(fn($user) => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'image_path' => asset(Storage::url($user->image_path))
        ]);
    }
}