<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Traits\SeederFileTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            ['role' => 'Client', 'email' => 'client1@gmail.com'],
            ['role' => 'Client', 'email' => 'client2@gmail.com'],
            ['role' => 'Lead Account Specialist', 'email' => 'leadAs@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as1@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as2@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as3@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as4@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as5@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as6@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as7@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as8@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as9@gmail.com'],
            ['role' => 'Account Specialist', 'email' => 'as10@gmail.com'],
            ['role' => 'Operations', 'email' => 'operations@gmail.com'],
            ['role' => 'Finance', 'email' => 'finance@gmail.com'],
            ['role' => 'Marketing', 'email' => 'marketing@gmail.com'],
            ['role' => 'Human Resource', 'email' => 'hr@gmail.com'],
            ['role' => 'IT', 'email' => 'it@gmail.com'],
        ];

        $profileImagePath = $this->copySeederFile('images', 'profile.jpg');
        $this->copySeederFile('images', 'jltcb.png');

        foreach($accounts as $account) {
            $user = User::create([
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->boolean() ? fake()->firstName() : null,
                'last_name' =>  fake()->lastName(),
                'username' => Str::before($account['email'], '@'),
                'email' => $account['email'],
                'password' => Hash::make('jltcb2025'),
                'address' => fake()->address(),
                'contact_number' => fake()->numerify('09#########'),
                'company_name' => ($account['role'] === 'Client') ? fake()->company() : 'JLTCB',
                'image_path' => $profileImagePath,
            ]);

            if ($account['role'] === 'Client') {
                $user->update([
                    'position' => fake()->randomElement(['Import Operations Specialist', 'Export Operations Specialist', 'Logistics Coordinator', 'Supply Chain Analyst']),
                    'business_type' => fake()->randomElement(['COOPERATIVE', 'CORPORATION', 'E-COMMERCE', 'INDIVIDUAL IMPORTER', 'GOVERNMENT AGENCY', 'IMPORT-EXPORT AGENT', 'MULTINATIONAL COMPANY', 'NON-PROFIT ORGANIZATION', 'PARTNERSHIP', 'PEZA-REGISTERED ENTERPRISE', 'SOLE PROPRIETORSHIP']),
                ]);
            }

            $user->assignRole($account['role']);
        }
    }
}
