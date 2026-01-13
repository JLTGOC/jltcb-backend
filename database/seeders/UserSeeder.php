<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['client', 'accountant', 'marketing', 'hr'];



        foreach($roles as $role) {
            $user = User::create([
                    'first_name' => fake()->firstName(),
                    'last_name' =>  fake()->lastName(),
                    'email' => $role . '@gmail.com',
                    'password' => 'password',
                    'address' => fake()->address(),
                    'contact_number' => fake()->numerify('09#########')
                ]);

            $user->assignRole($role);
        }
    }
}
