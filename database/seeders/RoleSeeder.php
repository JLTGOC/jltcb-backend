<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['Client', 'Lead Account Specialist', 'Account Specialist', 'Marketing', 'Human Resource', 'Operations', 'Finance', 'IT'];

        foreach ($roles as $role) {
              Role::create(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }
}
