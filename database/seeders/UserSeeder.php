<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['role' => 'Client', 'email' => 'client@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'accountspecialist@gmail.com'],
            ['role' => 'Marketing', 'email' => 'marketing@gmail.com'],
            ['role' => 'Human Resource', 'email' => 'humanresource@gmail.com'],
        ];

        foreach($accounts as $account) {
            $user = User::create([
                    'first_name' => fake()->firstName(),
                    'last_name' =>  fake()->lastName(),
                    'email' => $account['email'],
                    'password' => Hash::make($account['email']),
                    'address' => fake()->address(),
                    'contact_number' => fake()->numerify('09#########'),
                    'company_name' => ($account['role'] === 'Client') ? fake()->company() : null
                ]);

            $user->assignRole($account['role']);
        }
    }
}
