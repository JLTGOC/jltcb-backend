<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['client', 'accountant', 'marketing', 'hr'];

        foreach ($roles as $role) {
              Role::create(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }
}
