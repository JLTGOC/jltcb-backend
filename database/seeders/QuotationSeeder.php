<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
// use Faker\Factory as Faker;
use App\Models\{
    Quotation,
    User,
    Shipment
};
use Carbon\Carbon;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $faker = Faker::create();

        $clients = User::role('Client')->pluck('id');
        $specialists = User::role('Account Specialist')->pluck('id');

        $i = 0;
        do {
            $lastId = Quotation::max('id') ?? 0;
            $dateSection = Carbon::now()->format('m-Y');
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            $lastName = fake()->lastName();
            $firstName = fake()->firstName();
            $companyName = fake()->company();

            $quotation = Quotation::create([
                'reference_number' => "QT-{$dateSection}-{$idSection}",
                'status' => fake()->randomElement(['REQUESTED', 'RESPONDED']),
                'client_id' => fake()->randomElement($clients),
                'as_id' => fake()->randomElement($specialists),
                'company_name' => $companyName,
                'company_address' => fake()->address(),
                'contact_person' => $firstName . ' ' . $lastName,
                'contact_number' => fake()->numerify('09#########'),
                'email' => mb_strtolower($lastName) . '.' . mb_strtolower($firstName) . '@gmail.com',
                'service_type' => fake()->randomElement(['IMPORT', 'EXPORT', 'BUSINESS SOLUTION']),
                'transport_mode' => fake()->randomElement(['AIR', 'SEA']),
                'service_options' => 'PROJECT CARGO,POST CLEARANCE SERVICE',
                'commodity' => 'CASTABLE 16 REFRACTOR',
                'cargo_type' => fake()->randomElement(['CONTAINERIZED', 'LCL']),
                'origin' => fake()->address(),
                'destination' => fake()->address(),
                'remarks' => fake()->sentence(),
            ]);

            if ($quotation->cargo_type === 'CONTAINERIZED') {
                $quotation->update([
                    'container_size' => fake()->randomElement(['1x20', '1x40']),
                ]);
            } 
            // elseif ($quotation->cargo_type === 'LCL') {
            //     $quotation->update([
            //         'cargo_volume' => fake()->numberBetween(1, 15)
            //     ]);
            // }

            if ($quotation->status === 'RESPONDED') {
                $lastId = Shipment::max('id') ?? 0;
                $dateSection = Carbon::now()->format('m-Y');
                $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

                Shipment::create([
                    'reference_number' => "IM-{$dateSection}-{$idSection}",
                    'quotation_id' => $quotation->id,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'status' => fake()->randomElement(['ONGOING', 'DELIVERED']),
                    'company_name' => $quotation->company_name,
                    'contact_person' => $quotation->contact_person,
                    'contact_number' => $quotation->contact_number,
                    'email' => $quotation->email,
                    'commodity' => $quotation->commodity,
                    'cargo_type' => $quotation->cargo_type,
                    // 'cargo_volume' => $quotation->cargo_volume ?? null,
                    'container_size' => $quotation->container_size ?? null,
                    'origin' => $quotation->origin,
                    'destination' => $quotation->destination,
                    'remarks' => $quotation->remarks,
                ]);
            }

            $i+=1;
        } while ($i<10);
    }
}
