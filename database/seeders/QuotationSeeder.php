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
    JobOrderBilling,
    JobOrderClient,
    JobOrderShipment,
    LogisticsService,
    RegulatoryService,
    ServiceOption,
    BusinessType,
    RegulatoryAssistanceType,
    ContainerSize,
    ServiceLevel,
    BillingMode
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
            $status = fake()->randomElement(['REQUESTED', 'RESPONDED', 'ACCEPTED', 'ACCEPTED']);
            if ($status === 'ACCEPTED') {
                $assignmentStatus = 'ASSIGNED';
            } elseif ($status === 'RESPONDED') {
                $assignmentStatus = fake()->randomElement(['ASSIGNED', 'REASSIGNMENT REQUESTED']);
            } elseif ($status === 'REQUESTED') {
                $assignmentStatus = fake()->randomElement(['AVAILABLE', 'AVAILABLE', 'ASSIGNED', 'REASSIGNMENT REQUESTED']);
            }

            $assignedAt = $assignmentStatus === 'ASSIGNED' || $assignmentStatus === 'REASSIGNMENT REQUESTED' ? Carbon::now() : null;

            if ($assignmentStatus === 'ASSIGNED' || $assignmentStatus === 'REASSIGNMENT REQUESTED') {
                $assignedSpecialist = fake()->randomElement($specialists);
            } else {
                $assignedSpecialist = null;
            }

            $quotation = Quotation::create([
                'reference_number' => "QT-{$dateSection}-{$idSection}",
                'status' => $status,
                'client_id' => $client,
                'as_id' => $assignedSpecialist,
                'company_name' => $companyName,
                'company_address' => fake()->address(),
                'contact_person' => $firstName . ' ' . $lastName,
                'contact_number' => fake()->numerify('09#########'),
                'email' => mb_strtolower($lastName) . '.' . mb_strtolower($firstName) . '@gmail.com',
                'position' => User::find($client)->position,
                'assignment_status' => $assignmentStatus,
                'assigned_at' => $assignedAt,
                'created_at' => Carbon::now()->subDays(fake()->numberBetween(20, 30)),
                'updated_at' => Carbon::now()->subDays(fake()->numberBetween(10, 20)),
            ]);

            $quotation->files()->updateOrCreate([
                'quotation_id' => $quotation->id,
                'file_path' => 'files/ClientDoc.pdf' ?? 'files/ClientDoc1.pdf',
            ], [
                'uploaded_by' => $quotation->client_id,
                'type' => 'REQUESTED',
                'original_file_name' => 'DOCUMENT.pdf',
                'file_type' => 'pdf',
            ]);

            $serviceDomain = fake()->randomElement(['LOGISTICS', 'LOGISTICS', 'REGULATORY']);

            if ($serviceDomain === 'LOGISTICS') {
                $num = fake()->numberBetween(1, 5);
                $serviceOptions = '';
                while ($num > 0) {
                    $option = fake()->randomElement(ServiceOption::pluck('name')->toArray());
                    if ($option === 'ALL IN') {
                        $serviceOptions = 'ALL IN';
                        break;
                    }
                    $serviceOptions .= $option . ($num-1 > 0 ? ',' : '');
                    $num--;
                }
                $cargoType = fake()->randomElement(['CONTAINERIZED', 'LCL']);
                $containerSize = fake()->randomElement(ContainerSize::pluck('size')->toArray());

                LogisticsService::create([
                    'quotation_id' => $quotation->id,
                    'service_type' => fake()->randomElement(['IMPORT', 'EXPORT']),
                    'transport_mode' => fake()->randomElement(['AIR', 'SEA']),
                    'service_options' => $serviceOptions,
                    'commodity' => 'CASTABLE 16 REFRACTOR',
                    'cargo_type' => $cargoType,
                    'container_size' => $cargoType === 'CONTAINERIZED' ? $containerSize : null,
                    'origin' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
                    'destination' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
                    'remarks' => fake()->sentence(),
                    'created_at' => $quotation->created_at,
                    'updated_at' => $quotation->created_at,
                ]);
            } else {
                RegulatoryService::create([
                    'quotation_id' => $quotation->id,
                    'full_name' => $quotation->contact_person,
                    'contact_person_contact_number' => $quotation->contact_number,
                    'business_type' => $quotation->client->business_type,
                    'position' => $quotation->client->company_position,
                    'type_of_regulatory_assistance' => fake()->randomElement(RegulatoryAssistanceType::pluck('name')->toArray()),
                    'application_type' => fake()->randomElement(['NEW', 'RENEWAL']),
                    'message' => fake()->sentence(),
                    'created_at' => $quotation->created_at,
                    'updated_at' => $quotation->created_at,
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
                        'uploaded_by' => $quotation->as_id ?? fake()->randomElement($specialists),
                        'type' => 'PROPOSAL',
                        'original_file_name' => 'QUOTATION.pdf',
                        'file_type' => 'pdf',
                        'created_at' => $quotation->updated_at,
                        'updated_at' => $quotation->updated_at,
                    ]);
                } else {
                    $quotation->files()->where('file_path', 'files/QuotationFile.pdf')->where('quotation_id', $quotation->id)->update([
                        'type' => 'PROPOSAL',
                        'created_at' => $quotation->updated_at,
                        'updated_at' => $quotation->updated_at,
                    ]);
                }
            }

            if ($quotation->status === 'ACCEPTED') {
                $jobType = $quotation->logisticsService ? 'LOGISTICS' : 'REGULATORY';
                $prefix = $jobType === 'LOGISTICS' ? 'SJO' : 'SPL';
                
                $idSection = str_pad(((JobOrder::latest('id')->value('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);
                $dateSection = now()->format('m-Y');

                $jobOrder = JobOrder::create([
                    'reference_number' => "{$prefix}-{$dateSection}-{$idSection}",
                    'job_type' => $jobType,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'operations_id' => User::role('Operations')->inRandomOrder()->first()->id,
                    'finance_id' => User::role('Finance')->inRandomOrder()->first()->id,
                    'quotation_id' => $quotation->id,
                    'subject' => fake()->sentence(),
                    'email_body' => fake()->paragraph(),
                    'shipment_creation_status' => fake()->randomElement(['PENDING', 'CREATED']),
                    'created_at' => Carbon::now()->subDays(fake()->numberBetween(5, 10)),
                    'updated_at' => Carbon::now()->subDays(fake()->numberBetween(1, 5)),
                ]);

                if ($quotation->logisticsService) {
                    $serviceType = $quotation->logisticsService->service_type;
                } elseif ($quotation->regulatoryService) {
                    $serviceType = 'BOC New Importer Accreditation';
                }

                JobOrderClient::create([
                    'job_order_id' => $jobOrder->id,
                    'service_type' => $serviceType,
                    'client_type' => fake()->randomElement(['NEW', 'RENEWAL']),
                    'accredited' => fake()->randomElement(['REGULAR', 'EXPEDITED']),
                    'client_remarks' => fake()->sentence(),
                    'created_at' => $jobOrder->created_at,
                    'updated_at' => $jobOrder->created_at,
                ]);

                if ($quotation->logisticsService) {
                    JobOrderShipment::create([
                        'job_order_id' => $jobOrder->id,
                        'service_level' => fake()->randomElement(ServiceLevel::pluck('name')->toArray()),
                        'bl_no' => fake()->bothify('AMP0#####'),
                        'eta' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
                        'etd' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
                        'hs_code' => fake()->numerify('HS-#####'),
                        'shipment_remarks' => fake()->sentence(),
                        'target_delivery_date' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
                        'target_completion_date' => Carbon::now()->addDays(fake()->numberBetween(30, 60)),
                        'commitment_remarks' => fake()->sentence(),
                        'created_at' => $jobOrder->created_at,
                        'updated_at' => $jobOrder->created_at,
                    ]);

                    JobOrderBilling::create([
                        'job_order_id' => $jobOrder->id,
                        'terms_of_payment' => fake()->sentence(),
                        'billing_date' => Carbon::now()->addDays(fake()->numberBetween(60, 90)),
                        'shall_be_billed' => fake()->randomElement(BillingMode::pluck('name')->toArray()),
                        'created_at' => $jobOrder->created_at,
                        'updated_at' => $jobOrder->created_at,
                    ]);
                }

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
                        'job_order_id' => $jobOrder->id,
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
                        'finance_id' => null,
                    ]);
                }
            }

            $i+=1;
        } while ($i<30);
    }
}
