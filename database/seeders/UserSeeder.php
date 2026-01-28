<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Traits\SeederFileTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use SeederFileTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean up old files from public storage
        $this->cleanupSeederFiles('images');

        $accounts = [
            ['role' => 'Client', 'email' => 'client@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'accountspecialist@gmail.com'],
            ['role' => 'Marketing', 'email' => 'marketing@gmail.com'],
            ['role' => 'Human Resource', 'email' => 'humanresource@gmail.com'],
        ];

        $profileImagePath = $this->copySeederFile('images', 'profile.jpg');

        foreach($accounts as $account) {
            $user = User::create([
                    'first_name' => fake()->firstName(),
                    'middle_name' => fake()->boolean() ? fake()->firstName() : null,
                    'last_name' =>  fake()->lastName(),
                    'email' => $account['email'],
                    'password' => Hash::make($account['email']),
                    'address' => fake()->address(),
                    'contact_number' => fake()->numerify('09#########'),
                    'company_name' => ($account['role'] === 'Client') ? fake()->company() : 'JLTCB',
                    'image_path' => $profileImagePath,
                ]);

            $user->assignRole($account['role']);
        }
    }
}
