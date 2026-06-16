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
    JobOrderBillingFile,
    JobOrderClient,
    JobOrderShipment,
    LogisticsService,
    RegulatoryService,
    ServiceOption,
    BusinessType,
    RegulatoryAssistanceType,
    ContainerSize,
    ServiceLevel,
    BillingMode,
    ShipmentFile,
    ServiceType,
};
use Carbon\Carbon;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = User::role('Client')->limit(2)->pluck('id');
        $specialists = User::role(['Account Specialist', 'Lead Account Specialist', 'Client Success'])->pluck('id');
        $ops = User::role(['Operations' , 'Client Success'])->get();

        $i = 0;
        do {
            $serviceDomain = fake()->randomElement(['LOGISTICS', 'LOGISTICS', 'REGULATORY']);
            $serviceType = $this->resolveServiceType($serviceDomain);
            $reference = $this->generateReference($serviceDomain);
            $serviceOptions = $this->generateServiceOptions($serviceDomain, $serviceType->id);

            $lastName = fake()->lastName();
            $firstName = fake()->firstName();
            // $companyName = fake()->company();

            $client = fake()->randomElement($clients);
            $company = User::find($client)->company;
            $companyName = $company ? $company->name : fake()->company();
            $companyAddress = $company ? $company->address->registered_address : fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode();
            $status = fake()->randomElement(['REQUESTED', 'RESPONDED', 'ACCEPTED', 'ACCEPTED']);
            if ($status === 'ACCEPTED') {
                $assignmentStatus = fake()->randomElement(['ASSIGNED', 'REASSIGNMENT REQUESTED']);
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
                'reference_number' => $reference,
                'status' => $status,
                'client_id' => $client,
                'client_name' => User::find($client)->full_name,
                'service_type_id' => $serviceType->id,
                'as_id' => $assignedSpecialist,
                'service_options' => $serviceOptions,
                'commodity' => $serviceDomain === 'LOGISTICS' ? 'CASTABLE 16 REFRACTOR' : 'COMMODITY',
                'company_name' => $companyName,
                'company_address' => $companyAddress,
                'contact_person' => $firstName . ' ' . $lastName,
                'contact_number' => fake()->numerify('09#########'),
                'email' => mb_strtolower($lastName) . '.' . mb_strtolower($firstName) . '@gmail.com',
                'position' => User::find($client)->company_position ?? null,
                'assignment_status' => $assignmentStatus,
                'assigned_at' => $assignedAt,
                'created_at' => Carbon::now()->subDays(fake()->numberBetween(20, 30)),
                'updated_at' => Carbon::now()->subDays(fake()->numberBetween(10, 20)),
            ]);

            $uploadedBy = ($quotation->client?->id ?? $quotation->as_id) ?? fake()->randomElement($specialists);

            $this->attachClientFiles($quotation, $uploadedBy, 3, $quotation->created_at, $quotation->updated_at);

            if ($serviceDomain === 'LOGISTICS') {
                $this->createLogisticsService($quotation);
            } else {
                $this->createRegulatoryService($quotation);
            }

            if ($quotation->status === 'RESPONDED' || $quotation->status === 'ACCEPTED') {
                $monthYear = Carbon::now()->format('m-Y');
                $idSection = str_pad($quotation->id, 3, '0', STR_PAD_LEFT);
                $quotation->update([
                    'reference_number' => "QT-{$monthYear}-{$idSection}",
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
                        'document_checklist_item_id' => null,
                        'created_at' => $quotation->updated_at,
                        'updated_at' => $quotation->updated_at,
                    ]);
                } else {
                    $quotation->files()->where('file_path', 'files/QuotationFile.pdf')->where('quotation_id', $quotation->id)->update([
                        'type' => 'PROPOSAL',
                        'document_checklist_item_id' => null,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                    ]);
                }
            }

            $jobOrderCreated = fake()->boolean();

            if ($assignedSpecialist && User::find($assignedSpecialist)->value('username') === 'csd1') {
                $jobOrderCreated = false;
            }

            if ($quotation->status === 'ACCEPTED' && $jobOrderCreated) {
                $jobType = $quotation->logisticsService ? 'LOGISTICS' : 'REGULATORY';
                $prefix = $jobType === 'LOGISTICS' ? 'SJO' : 'SPL';
                
                $idSection = str_pad(((JobOrder::latest('id')->value('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);
                $dateSection = now()->format('m-Y');

                $assignedOps = null;

                $assignmentStatus = fake()->randomElement(['AVAILABLE', 'ASSIGNED', 'REASSIGNMENT REQUESTED']);
                if ($assignmentStatus === 'ASSIGNED' || $assignmentStatus === 'REASSIGNMENT REQUESTED') {
                    if ($ops->isNotEmpty()) {
                        $assignedOps = $ops->random();
                    } else {
                        $assignmentStatus = 'AVAILABLE';
                    }
                }

                $jobOrder = JobOrder::create([
                    'reference_number' => "{$prefix}-{$dateSection}-{$idSection}",
                    'job_type' => $jobType,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'operations_id' => $assignedOps?->id,
                    'quotation_id' => $quotation->id,
                    'subject' => fake()->sentence(),
                    'email_body' => fake()->paragraph(),
                    'shipment_creation_status' => fake()->randomElement(['PENDING', 'CREATED']),
                    'assignment_status' => $assignmentStatus,
                    'created_at' => Carbon::now()->subDays(fake()->numberBetween(5, 10)),
                    'updated_at' => Carbon::now()->subDays(fake()->numberBetween(1, 5)),
                ]);

                $jobOrder->update(['date_issued' => $jobOrder->created_at->toDateString()]);

                if ($jobOrder->assignment_status === 'ASSIGNED' || $jobOrder->assignment_status === 'REASSIGNMENT REQUESTED') {
                    $jobOrder->update([
                        'assigned_at' => $jobOrder->updated_at,
                    ]);
                } elseif ($jobOrder->assignment_status === 'AVAILABLE') {
                    $jobOrder->update([
                        'operations_id' => null,
                        'shipment_creation_status' => 'PENDING',
                    ]);
                }

                // if ($quotation->logisticsService) {
                //     $serviceType = $quotation->serviceType?->name;
                // } elseif ($quotation->regulatoryService) {
                //     $serviceType = 'BOC New Importer Accreditation';
                // }

                $serviceType = $quotation->serviceType?->name;

                JobOrderClient::create([
                    'job_order_id' => $jobOrder->id,
                    'service_type_id' => ServiceType::where('name', $serviceType)->first()?->id,
                    'client_type' => fake()->randomElement(['NEW', 'RENEWAL']),
                    'accredited' => fake()->randomElement(['REGULAR', 'EXPEDITED']),
                    'tone_and_attitude' => fake()->randomElement(['FRIENDLY', 'NEUTRAL', 'HOSTILE']),
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

                    $billing = JobOrderBilling::create([
                        'job_order_id' => $jobOrder->id,
                        'terms_of_payment' => fake()->sentence(),
                        'billing_date' => Carbon::now()->addDays(fake()->numberBetween(60, 90)),
                        'shall_be_billed' => fake()->randomElement(BillingMode::pluck('name')->toArray()),
                        'listed_docs' => 'INVOICE, RECEIPT',
                        'created_at' => $jobOrder->created_at,
                        'updated_at' => $jobOrder->created_at,
                    ]);

                    JobOrderBillingFile::create([
                        'job_order_billing_id' => $billing->id,
                        'file_path' => 'files/QuotationFile.pdf',
                        'file_name' => 'QuotationFile.pdf',
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

                    $prefix = $quotation->serviceType?->code ?? 'IM';
                    $lastId = Shipment::max('id') ?? 0;
                    $dateSection = Carbon::now()->format('m-Y');
                    $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);
                    
                    Shipment::create([
                        'reference_number' => "{$prefix}-{$dateSection}-{$idSection}",
                        'quotation_id' => $quotation->id,
                        'job_order_id' => $jobOrder->id,
                        'client_id' => $quotation->client_id,
                        'as_id' => $quotation->as_id,
                        'operations_id' => $jobOrder->operations_id,
                        'status' => fake()->randomElement(['NOT YET DEPARTED', 'IN TRANSIT', 'ARRIVED', 'BERTHED', 'DISCHARGED', 'DELIVERED']),
                        'company_name' => $quotation->company_name,
                        'contact_person' => $quotation->contact_person,
                        'contact_number' => $quotation->contact_number,
                        'email' => $quotation->email,
                        'commodity' => $quotation->commodity,
                        'cargo_type' => $logisticsService->cargo_type,
                        'container_size' => $logisticsService->container_size,
                        'origin' => $logisticsService->origin,
                        'destination' => $logisticsService->destination,
                        'remarks' => $logisticsService->remarks,
                        'created_at' => Carbon::parse($jobOrder->created_at)->addHours(fake()->numberBetween(1, 5)),
                        'updated_at' => Carbon::now()
                    ]);

                    $quotationFiles = $quotation->files;
                    foreach ($quotationFiles as $file) {
                        ShipmentFile::create([
                            'shipment_id' => Shipment::latest('id')->value('id'),
                            'quotation_file_id' => $file->id,
                        ]);
                    }
                }
            }

            $i+=1;
        } while ($i<91);

        $csd1Quotations = Quotation::whereHas('accountSpecialist', function($query) {
            $query->where('username', 'csd1');
        })->where('status', 'ACCEPTED')->count();

        if ($csd1Quotations < 5) {
            $quotationsToUpdate = Quotation::whereHas('accountSpecialist', function ($query) {
                $query->where('status', 'ACCEPTED')
                    ->where('username', '!=', 'csd1');
            })->limit(5 - $csd1Quotations)->get();

            foreach ($quotationsToUpdate as $quotation) {
                $quotation->update([
                    'as_id' => User::where('username', 'csd1')->value('id'),
                ]);

                if ($quotation->jobOrder) {
                    if ($quotation->shipment) {
                        $quotation->shipment->delete();
                    }
                    $quotation->jobOrder->delete();
                }
            }
        }

        // Quotations for new clients
        $newClients = User::role('Client')->whereDoesntHave('quotations')->pluck('id');

        foreach ($newClients as $clientId) {
            $serviceDomain = fake()->randomElement(['LOGISTICS', 'REGULATORY']);
            $serviceType = $this->resolveServiceType($serviceDomain);
            $serviceOptions = $this->generateServiceOptions($serviceDomain, $serviceType->id);
            $serviceSection = $serviceDomain === 'LOGISTICS' ? 'LOG' : 'REG';
            $dateSection = Carbon::now()->format('mdY');
            $lastId = Quotation::max('id') ?? 0;
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            $q = Quotation::create([
                'reference_number' => "RQ-{$serviceSection}-{$dateSection}-{$idSection}",
                'status' => 'REQUESTED',
                'client_id' => $clientId,
                'client_name' => User::find($clientId)->full_name,
                'service_type_id' => $serviceType->id,
                'service_options' => $serviceOptions,
                'commodity' => $serviceDomain === 'LOGISTICS' ? 'CASTABLE 16 REFRACTOR' : 'COMMODITY',
                'company_name' => User::find($clientId)->company ? User::find($clientId)->company->name : fake()->company(),
                'company_address' => User::find($clientId)->company ? User::find($clientId)->company->address->registered_address : fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
                'contact_person' => User::find($clientId)->full_name,
                'contact_number' => fake()->numerify('09#########'),
                'email' => User::find($clientId)->email,
                'position' => User::find($clientId)->company_position ?? null,
                'assignment_status' => 'AVAILABLE',
                'assigned_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $this->attachClientFiles($q, $q->client_id, 3, $q->created_at, $q->updated_at);

            if ($serviceDomain === 'LOGISTICS') {
                $this->createLogisticsService($q);
            } else {
                $this->createRegulatoryService($q, ['application_type' => 'NEW']);
            }
        }

        // Quotations for unregistered clients
        for ($j=0; $j<5; $j++) {
            $serviceDomain = fake()->randomElement(['LOGISTICS', 'LOGISTICS', 'REGULATORY']);
            $serviceType = $this->resolveServiceType($serviceDomain);
            $serviceOptions = $this->generateServiceOptions($serviceDomain, $serviceType->id);
            $serviceSection = $serviceDomain === 'LOGISTICS' ? 'LOG' : 'REG';
            $dateSection = Carbon::now()->format('mdY');
            $lastId = Quotation::max('id') ?? 0;
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            $q = Quotation::create([
                'reference_number' => "RQ-{$serviceSection}-{$dateSection}-{$idSection}",
                'status' => 'REQUESTED',
                'client_id' => null,
                'client_name' => fake()->firstName() . ' ' . fake()->lastName(),
                'service_type_id' => $serviceType->id,
                'service_options' => $serviceOptions,
                'commodity' => $serviceDomain === 'LOGISTICS' ? 'CASTABLE 16 REFRACTOR' : 'COMMODITY',
                'as_id' => null,
                'company_name' => fake()->company(),
                'company_address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
                'contact_person' => fake()->name(),
                'contact_number' => fake()->numerify('09#########'),
                'email' => fake()->unique()->safeEmail(),
                'position' => null,
                'assignment_status' => 'AVAILABLE',
                'assigned_at' => null,
                'created_at' => Carbon::now()->subDays(fake()->numberBetween(20, 30)),
                'updated_at' => Carbon::now()->subDays(fake()->numberBetween(10, 20)),
            ]);

            $this->attachClientFiles($q, $q->client_id, 3, $q->created_at, $q->updated_at);

            if ($serviceDomain === 'LOGISTICS') {
                $this->createLogisticsService($q);
            } else {
                $this->createRegulatoryService($q, ['application_type' => 'NEW']);
            }
        }

    }

    private function generateReference(string $serviceDomain): string
    {
        $serviceSection = $serviceDomain === 'LOGISTICS' ? 'LOG' : 'REG';
        $dateSection = Carbon::now()->format('mdY');
        $lastId = Quotation::max('id') ?? 0;
        $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

        return "RQ-{$serviceSection}-{$dateSection}-{$idSection}";
    }

    private function resolveServiceType(string $serviceDomain): ServiceType
    {
        return ServiceType::query()
            ->where('service', $serviceDomain)
            ->inRandomOrder()
            ->firstOrFail();
    }

    private function attachClientFiles($quotation, $uploadedBy = null, $count = 3, $createdAt = null, $updatedAt = null)
    {
        if (!$uploadedBy) {
            $uploadedBy = $quotation->client_id ?? fake()->randomElement(User::role(['Account Specialist', 'Lead Account Specialist', 'Client Success'])->pluck('id'));
        }
        for ($n = 0; $n < $count; $n++) {
            $quotation->files()->updateOrCreate([
                'quotation_id' => $quotation->id,
                'file_path' => 'files/ClientDoc' . ($n + 1) . '.pdf',
            ], [
                'uploaded_by' => $uploadedBy,
                'type' => 'REQUESTED',
                'original_file_name' => 'DOCUMENT.pdf',
                'file_type' => 'pdf',
                'document_checklist_item_id' => $quotation->serviceType?->service === 'LOGISTICS' ? fake()->randomElement([1,3,4,5]) : fake()->randomElement([2,3,5]),
                'created_at' => $createdAt ?? $quotation->created_at,
                'updated_at' => $updatedAt ?? $quotation->updated_at,
            ]);
        }
    }

    private function createLogisticsService($quotation, array $overrides = [])
    {
        $cargoType = fake()->randomElement(['CONTAINERIZED', 'LCL']);
        $containerSize = fake()->randomElement(ContainerSize::pluck('size')->toArray());

        return LogisticsService::create(array_merge([
            'quotation_id' => $quotation->id,
            'transport_mode' => fake()->randomElement(['AIR', 'SEA']),
            'cargo_type' => $cargoType,
            'container_size' => $cargoType === 'CONTAINERIZED' ? $containerSize : null,
            'origin' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
            'destination' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
            'remarks' => fake()->sentence(),
            'created_at' => $quotation->created_at,
            'updated_at' => $quotation->created_at,
        ], $overrides));
    }

    private function createRegulatoryService($quotation, array $overrides = [])
    {
        return RegulatoryService::create(array_merge([
            'quotation_id' => $quotation->id,
            'full_name' => $quotation->contact_person,
            'contact_person_contact_number' => $quotation->contact_number,
            'business_type' => $quotation->client?->company ? $quotation->client->company->businessType->name : $quotation->client?->company?->business_type_other ?? null,
            'position' => $quotation->client?->company_position ?? null,
            'type_of_regulatory_assistance' => fake()->randomElement(RegulatoryAssistanceType::pluck('name')->toArray()),
            'application_type' => $overrides['application_type'] ?? fake()->randomElement(['NEW', 'RENEWAL']),
            'message' => fake()->sentence(),
            'created_at' => $quotation->created_at,
            'updated_at' => $quotation->created_at,
        ], $overrides));
    }

    private function generateServiceOptions($serviceDomain,$serviceTypeId)
    {
        $serviceOptions = '';
        if ($serviceDomain === 'LOGISTICS') {
            $optionsPool = ServiceOption::where('service_type_id', $serviceTypeId)->orWhereNull('service_type_id')->pluck('name')->toArray();
            $num = fake()->numberBetween(1, 5);
        } else {
            $optionsPool = ServiceOption::where('service_type_id', $serviceTypeId)->pluck('name')->toArray();
            $num = fake()->numberBetween(1, 2);
        }
        
        while ($num > 0) {
            $option = fake()->randomElement($optionsPool);
            if ($option === 'ALL IN') {
                $serviceOptions = 'ALL IN';
                break;
            }
            $serviceOptions .= $option . ($num - 1 > 0 ? ', ' : '');
            $num--;
        }
        return $serviceOptions;
    }
}
