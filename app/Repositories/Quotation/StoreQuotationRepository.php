<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use App\Models\User;
use App\Models\ActivityLog;
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
        $user = User::find(auth()->id());

        DB::beginTransaction();

        try {
            $serviceSection = $request->input('services') === 'LOGISTICS' ? 'LOG' : 'REG';
            $dateSection = Carbon::now()->format('mdY');
            $lastId = Quotation::max('id') ?? 0;
            $idSection = str_pad($lastId+1, 3, '0', STR_PAD_LEFT);

            $previousQuotation = Quotation::where('client_id', $user->id)->latest()->first();
            $assignedSpecialist = null;
            if ($previousQuotation) {
                $assignedSpecialist = $previousQuotation->accountSpecialist;
            } 

            if ($assignedSpecialist) {
                $assignmentStatus = 'ASSIGNED';
                $assignedAt = Carbon::now();
            }

            $quotation = Quotation::create([
                'reference_number' => "RQ-{$serviceSection}-{$dateSection}-{$idSection}",
                'client_id' => $user->id,
                'as_id' => $assignedSpecialist?->id ?? null,
                'company_name' => $request->input('company.name'),
                'company_address' => $request->input('company.address'),
                'contact_person' => $request->input('company.contact_person'),
                'contact_number' => $request->input('company.contact_number'),
                'email' => $request->input('company.email'),
                'position' => $request->input('company.position'),
                'assignment_status' => $assignmentStatus ?? 'AVAILABLE',
                'assigned_at' => $assignedAt ?? null
            ]);

            if ($request->input('services') === 'LOGISTICS') {
                $stringifiedServiceOptions = implode(',', $request->service['options']);
                $specialists = User::role('Account Specialist')->pluck('id');

                $logisticsService = $quotation->logisticsService()->create([
                    'service_type' => $request->service['type'],
                    'transport_mode' => $request->service['transport_mode'],
                    'service_options' => $stringifiedServiceOptions,
                    'commodity' => $request->commodity['commodity'],
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
                    'business_type' => $request->input('company.business_type'),
                    'position' => $request->input('company.position'),
                    'type_of_regulatory_assistance' => $typeOfRegulatoryAssistance,
                    'application_type' => $request->service_level,
                    'message' => $request->message ?? null,
                ]);
            }

            // Upload client documents
            $fileUploaded = $this->quotationFileService->syncClientDocuments(
                $quotation, $user, newFiles: $request->file('documents')
            );

            if ($fileUploaded !== true) {
                return $this->error($fileUploaded->getMessage());
            }

            $activityLog = ActivityLog::create([
                'subject_id' => $quotation->id,
                'subject_type' => Quotation::class,
                'user_id' => $user->id,
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
