<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
// use Faker\Factory as Faker;
use App\Models\{
    Quotation,
    User,
    Shipment,
    JobOrder,
    LogisticsService,
    RegulatoryService,
};
use Carbon\Carbon;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

            $client = fake()->randomElement($clients);

            $quotation = Quotation::create([
                'reference_number' => "QT-{$dateSection}-{$idSection}",
                'status' => fake()->randomElement(['REQUESTED', 'RESPONDED', 'ACCEPTED']),
                'client_id' => $client,
                'as_id' => fake()->randomElement($specialists),
                'company_name' => $companyName,
                'company_address' => fake()->address(),
                'contact_person' => $firstName . ' ' . $lastName,
                'contact_number' => fake()->numerify('09#########'),
                'email' => mb_strtolower($lastName) . '.' . mb_strtolower($firstName) . '@gmail.com',
                'position' => User::find($client)->position,
            ]);

            $serviceDomain = fake()->randomElement(['LOGISTICS', 'LOGISTICS', 'REGULATORY']);

            if ($serviceDomain === 'LOGISTICS') {
                $cargoType = fake()->randomElement(['CONTAINERIZED', 'LCL']);

                LogisticsService::create([
                    'quotation_id' => $quotation->id,
                    'service_type' => fake()->randomElement(['IMPORT', 'EXPORT']),
                    'transport_mode' => fake()->randomElement(['AIR', 'SEA']),
                    'service_options' => 'PROJECT CARGO,POST CLEARANCE SERVICE',
                    'commodity' => 'CASTABLE 16 REFRACTOR',
                    'cargo_type' => $cargoType,
                    'container_size' => $cargoType === 'CONTAINERIZED' ? fake()->randomElement(['1x20', '1x40']) : null,
                    'origin' => fake()->address(),
                    'destination' => fake()->address(),
                    'remarks' => fake()->sentence(),
                ]);
            } else {
                RegulatoryService::create([
                    'quotation_id' => $quotation->id,
                    'business_type' => fake()->randomElement([
                        'COOPERATIVE',
                        'CORPORATION',
                        'E-COMMERCE',
                        'INDIVIDUAL IMPORTER',
                        'GOVERNMENT AGENCY',
                        'IMPORT-EXPORT AGENT',
                        'MULTINATIONAL COMPANY',
                        'NON-PROFIT ORGANIZATION',
                        'PARTNERSHIP',
                        'PEZA-REGISTERED ENTERPRISE',
                        'SOLE PROPRIETORSHIP',
                    ]),
                    'type_of_regulatory_assistance' => 'FOOD AND DRUG ADMINISTRATION (FDA)',
                    'application_type' => fake()->randomElement(['NEW', 'RENEWAL']),
                    'message' => fake()->sentence(),
                ]);
            }

            if ($quotation->status === 'RESPONDED' || $quotation->status === 'ACCEPTED') {
                $quotation->update([
                    'created_by' => $quotation->as_id,
                ]);

                if (!$quotation->files()->where('file_path', 'files/QuotationFile.pdf')->where('quotation_id', $quotation->id)->exists()) {
                    $quotation->files()->updateOrCreate([
                        'quotation_id' => $quotation->id,
                        'file_path' => 'files/QuotationFile.pdf',
                    ], [
                        'uploaded_by' => $quotation->as_id,
                        'type' => 'PROPOSAL',
                        'original_file_name' => 'QUOTATION.pdf',
                        'file_type' => 'application/pdf',
                    ]);
                } else {
                    $quotation->files()->where('file_path', 'files/QuotationFile.pdf')->where('quotation_id', $quotation->id)->update([
                        'type' => 'PROPOSAL',
                    ]);
                }
            }

            if ($quotation->status === 'ACCEPTED' && $quotation->logisticsService) {
                $idSection = str_pad(((JobOrder::latest('id')->value('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);
                $prefix = 'SJO';
                $dateSection = now()->format('m-Y');

                $jobOrder = JobOrder::create([
                    'reference_number' => "{$prefix}-{$dateSection}-{$idSection}",
                    'job_type' => 'SHIPMENT',
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'operations_id' => User::role('Operations')->inRandomOrder()->first()->id,
                    'finance_id' => User::role('Finance')->inRandomOrder()->first()->id,
                    'quotation_id' => $quotation->id,
                    'subject' => fake()->sentence(),
                    'email_body' => fake()->paragraph(),
                    'client_type' => fake()->randomElement(['NEW', 'RENEWAL']),
                    'accredited' => fake()->randomElement(['REGULAR', 'EXPEDITED']),
                    'client_remarks' => fake()->sentence(),
                    'service_level' => fake()->randomElement([
                        'CARGO CONSOLIDATION (CC)',
                        'DIRECT EXPORT (DE)',
                        'INTERNATIONAL FREIGHT FORWARDING (IFF)',
                        'CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
                        'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC)',
                        'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
                    ]),
                    'bl_no' => fake()->bothify('BL-#####'),
                    'eta' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
                    'etd' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
                    'hs_code' => fake()->numerify('HS-#####'),
                    'shipment_remarks' => fake()->sentence(),
                    'target_delivery_date' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
                    'target_completion_date' => Carbon::now()->addDays(fake()->numberBetween(30, 60)),
                    'commitment_remarks' => fake()->sentence(),
                    'terms_of_payment' => fake()->sentence(),
                    'billing_date' => Carbon::now()->addDays(fake()->numberBetween(60, 90)),
                    'shall_be_billed' => 'AS PER QUOTE',
                    'shipment_creation_status' => fake()->randomElement(['PENDING', 'CREATED']),
                ]);

                if ($jobOrder->shipment_creation_status === 'CREATED') {
                    $logisticsService = $quotation->logisticsService;
                    if (!$logisticsService) {
                        $i += 1;
                        continue;
                    }

                    if ($logisticsService->service_type === 'IMPORT') {
                        $prefix = 'IM';
                    } elseif ($logisticsService->service_type === 'EXPORT') {
                        $prefix = 'EX';
                    }
                    $lastId = Shipment::max('id') ?? 0;
                    $dateSection = Carbon::now()->format('m-Y');
                    $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);
                    
                    Shipment::create([
                        'reference_number' => "{$prefix}-{$dateSection}-{$idSection}",
                        'quotation_id' => $quotation->id,
                        'client_id' => $quotation->client_id,
                        'as_id' => $quotation->as_id,
                        'status' => fake()->randomElement(['PENDING', 'NOT YET DELIVERED', 'IN TRANSIT', 'ARRIVED', 'BERTHED', 'DISCHARGED', 'DELIVERED']),
                        'company_name' => $quotation->company_name,
                        'contact_person' => $quotation->contact_person,
                        'contact_number' => $quotation->contact_number,
                        'email' => $quotation->email,
                        'commodity' => $logisticsService->commodity,
                        'cargo_type' => $logisticsService->cargo_type,
                        'container_size' => $logisticsService->container_size,
                        'origin' => $logisticsService->origin,
                        'destination' => $logisticsService->destination,
                        'remarks' => $logisticsService->remarks,
                    ]);
                } else {
                    $jobOrder->update([
                        'operations_id' => null,
                    ]);
                }
            }

            $i+=1;
        } while ($i<20);
    }
}
