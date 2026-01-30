<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
// use Faker\Factory as Faker;
use App\Models\{
    Quotation,
    User
};
use App\Traits\Generator;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
    use Generator;

    public function run(): void
    {
        // $faker = Faker::create();

        $clients = User::role('Client')->pluck('id');
        $specialists = User::role('Account Specialist')->pluck('id');

        $i = 0;
        do {
            $lastName = fake()->lastName();
            $firstName = fake()->firstName();
            $companyName = fake()->company();
            $quotation = Quotation::create([
                'reference_number' => $this->quotationReferenceNumber(),
                'status' => fake()->randomElement(['REQUESTED', 'REQUESTED', 'RESPONDED']),
                'client_id' => fake()->randomElement($clients),
                'as_id' => fake()->randomElement($specialists),
                'company_name' => $companyName,
                'company_address' => fake()->address(),
                'contact_person' => $firstName . ' ' . $lastName,
                'contact_number' => fake()->numerify('09#########'),
                'email' => $lastName . '.' . $firstName . '@' . $companyName . '.com',
                'service_type' => fake()->randomElement(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION']),
                'transport_mode' => fake()->randomElement(['AIR', 'SEA']),
                'service_options' => 'ALL IN',
                'commodity' => 'CASTABLE 16 REFRACTOR',
                'cargo_volume' => fake()->randomElement(['CONTAINERIZED', 'LCL']),
                'origin' => fake()->address(),
                'destination' => fake()->address(),
            ]);

            if ($quotation->cargo_volume === 'CONTAINERIZED') {
                $quotation->update([
                    'container_size' => fake()->randomElement(['1x20', '1x40']),
                ]);
            }

            $i+=1;
        } while ($i<5);
    }
}
