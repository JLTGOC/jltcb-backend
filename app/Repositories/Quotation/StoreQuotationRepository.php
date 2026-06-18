<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\ServiceType;
use App\Repositories\BaseRepository;
use App\Services\QuotationFileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreQuotationRepository extends BaseRepository
{
    protected $quotationFileService;

    public function __construct(QuotationFileService $quotationFileService)
    {
        $this->quotationFileService = $quotationFileService;
    }

    public function execute($request){
        $user = User::find($request->input('client'));
        $clientName = $user ? $user->full_name : null;
        if ($user === null) {
            $clientName = $request->input('client');
        }

        if (auth()->user()->hasRole('Client')) {
            $user = User::find(auth()->id());
            $clientName = $user->full_name;
        }
        
        DB::beginTransaction();

        try {
            $serviceSection = $request->input('services') === 'LOGISTICS' ? 'LOG' : 'REG';
            $dateSection = Carbon::now()->format('mdY');
            $lastId = Quotation::max('id') ?? 0;
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            if ($user) {
                $assignedSpecialist = $user->company ? $user->company->accountHandler : null;
                $assignmentStatus = $assignedSpecialist ? 'ASSIGNED' : 'AVAILABLE';
                $assignedAt = $assignedSpecialist ? Carbon::now() : null;
            }

            $stringifiedServiceOptions = implode(',', $request->service['options']);

            $quotation = Quotation::create([
                'reference_number' => "RQ-{$serviceSection}-{$dateSection}-{$idSection}",
                'service_type_id' => ServiceType::where('name', $request->input('service.type'))->first()->id ?? null,
                'client_id' => $user ? $user->id : null,
                'client_name' => $clientName,
                'as_id' => $assignedSpecialist?->id ?? null,
                'service_options' => $stringifiedServiceOptions,
                'commodity' => $request->input('commodity.commodity'),
                'company_name' => $request->input('company.name'),
                'company_address' => $request->input('company.address'),
                'contact_person' => $request->input('company.contact_person'),
                'contact_number' => $request->input('company.contact_number'),
                'email' => $request->input('company.email'),
                'position' => $request->input('company.position') ?? null,
                'consignee' => $request->input('company.consignee') ?? null,
                'assignment_status' => $assignmentStatus ?? 'AVAILABLE',
                'assigned_at' => $assignedAt ?? null
            ]);

            if ($request->input('services') === 'LOGISTICS') {
                $specialists = User::role('Account Specialist')->pluck('id');

                $logisticsService = $quotation->logisticsService()->create([
                    // 'service_type' => $request->service['type'],
                    'transport_mode' => $request->service['transport_mode'],
                    'cargo_type' => $request->commodity['cargo_type'],
                    'container_size' => $request->commodity['container_size'] ?? null,
                    'origin' => $request->shipment['origin'],
                    'destination' => $request->shipment['destination'],
                    'remarks' => $request->remarks ?? null,
                ]);

                if ($quotation->cargo_type === 'LCL' && isset($quotation->container_size)) {
                    $quotation->update([
                        'container_size' => null
                    ]);
                }
            } elseif ($request->input('services') === 'REGULATORY') {
                $typeOfRegulatoryAssistance = implode(',', $request->type_of_regulatory_assistance);

                $regulatoryService = $quotation->regulatoryService()->create([
                    'full_name' => $request->input('full_name'),
                    'contact_person_contact_number' => $request->input('company.cp_contact_number'),
                    'business_type' => $request->input('company.business_type') ?? null,
                    'position' => $request->input('company.position') ?? null,
                    'type_of_regulatory_assistance' => $typeOfRegulatoryAssistance,
                    'application_type' => $request->service_level,
                    'message' => $request->message ?? null,
                ]);
            }

            // Upload client documents
            $fileUploaded = $this->quotationFileService->syncClientDocuments(
                $quotation, auth()->user(), newFiles: $request->documents ?? []
            );

            if ($fileUploaded !== true) {
                return $this->error($fileUploaded->getMessage());
            }

            $activityLog = ActivityLog::create([
                'subject_id' => $quotation->id,
                'subject_type' => Quotation::class,
                'user_id' => auth()->id(),
                'action' => 'Quotation Requested',
            ]);

            DB::commit();

            return $this->success('Quotation request submitted', new QuotationResource($quotation), 200);
        } catch (ValidationException $e) {
            DB::rollback();
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollback();
            return $this->error('Something went wrong', 400, $e->getMessage());
        }
    }
}
