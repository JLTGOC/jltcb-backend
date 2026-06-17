<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Models\Shipment;
use App\Models\User;
use App\Models\ServiceType;
use App\Repositories\BaseRepository;
use App\Services\QuotationFileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateQuotationRepository extends BaseRepository
{
    protected $quotationFileService;

    public function __construct(QuotationFileService $quotationFileService)
    {
        $this->quotationFileService = $quotationFileService;
    }

    public function execute($request, $quotation){
        $user = User::find(auth()->id());

        if (((int) $user->id === (int) $quotation->client_id) || ((int) $user->id === (int) $quotation->as_id) || $user->hasRole('Lead Account Specialist')) {
            try {
                DB::beginTransaction();

                $accepted = $quotation->status === 'ACCEPTED';
                $shipped = Shipment::where('quotation_id', $quotation->id)->first();
                if ($accepted) {
                    return $this->error('Quotation already accepted', 422);
                }
                if ($shipped) {
                    return $this->error('Shipment already ongoing', 422);
                }

                $serviceOptions = $request->has('service.options')
                    ? implode(',', $request->input('service.options', []))
                    : $quotation->logisticsService?->service_options;

                $serviceType = $request->input('services');

                if (!$serviceType) {
                    if ($quotation->logisticsService) {
                        $serviceType = 'LOGISTICS';
                    } elseif ($quotation->regulatoryService) {
                        $serviceType = 'REGULATORY';
                    } elseif ($request->hasAny(['company.business_type', 'business_type', 'type_of_regulatory_assistance', 'service_level', 'message'])) {
                        $serviceType = 'REGULATORY';
                    } elseif ($request->hasAny(['service', 'commodity', 'shipment', 'remarks'])) {
                        $serviceType = 'LOGISTICS';
                    }
                }

                $quotation->update([
                    'company_name' => $request->input('company.name', $quotation->company_name),
                    'company_address' => $request->input('company.address', $quotation->company_address),
                    'contact_person' => $request->input('company.contact_person', $quotation->contact_person),
                    'contact_number' => $request->input('company.contact_number', $quotation->contact_number),
                    'email' => $request->input('company.email', $quotation->email),
                    'service_type_id' => ServiceType::where('name', $request->input('service.type'))->first()->id ?? null,
                    'service_options' => $serviceOptions,
                    'commodity' => $request->input('commodity.commodity', $quotation->commodity),
                ]);

                if ($serviceType === 'REGULATORY') {
                    $typeOfRegulatoryAssistance = $request->has('type_of_regulatory_assistance')
                        ? implode(',', $request->input('type_of_regulatory_assistance', []))
                        : $quotation->regulatoryService?->type_of_regulatory_assistance;

                    $quotation->regulatoryService()->updateOrCreate(
                        ['quotation_id' => $quotation->id],
                        [
                            'full_name' => $request->input('full_name', $quotation->regulatoryService?->full_name),
                            'contact_person_contact_number' => $request->input('company.cp_contact_number', $quotation->regulatoryService?->contact_person_contact_number),
                            'business_type' => $request->input('company.business_type', $quotation->regulatoryService?->business_type),
                            'position' => $request->input('company.position', $quotation->regulatoryService?->position),
                            'type_of_regulatory_assistance' => $typeOfRegulatoryAssistance,
                            'application_type' => $request->input('service_level', $quotation->regulatoryService?->application_type),
                            'message' => $request->input('message', $quotation->regulatoryService?->message),
                        ]
                    );

                    if ($quotation->logisticsService) {
                        $quotation->logisticsService()->delete();
                    }

                } elseif ($serviceType === 'LOGISTICS') {
                    $incomingCargoType = $request->input('commodity.cargo_type', $quotation->logisticsService?->cargo_type);
                    $containerSize = $request->input('commodity.container_size', $quotation->logisticsService?->container_size);
                    if ($incomingCargoType === 'LCL') {
                        $containerSize = null;
                    }

                    $quotation->logisticsService()->updateOrCreate(
                        ['quotation_id' => $quotation->id],
                        [
                            'transport_mode' => $request->input('service.transport_mode', $quotation->logisticsService?->transport_mode),
                            'cargo_type' => $incomingCargoType,
                            'container_size' => $containerSize,
                            'origin' => $request->input('shipment.origin', $quotation->logisticsService?->origin),
                            'destination' => $request->input('shipment.destination', $quotation->logisticsService?->destination),
                            'remarks' => $request->input('remarks', $quotation->logisticsService?->remarks),
                        ]
                    );

                    if ($quotation->regulatoryService) {
                        $quotation->regulatoryService()->delete();
                    }
                }

                 // Re-upload client documents
                $fileUploaded = $this->quotationFileService->syncClientDocuments(
                    $quotation, 
                    $user,
                    newFiles: $request->documents, 
                    removedFileIds: $request->input('removed_documents', []) 
                );

                if ($fileUploaded !== true) {
                    return $this->error($fileUploaded->getMessage());
                }

                DB::commit();

                return $this->success('Quotation request updated', new QuotationResource($quotation), 200);

            } catch (ValidationException $e) {
                DB::rollback();
                return $this->error('Validation failed', 422, $e->errors());
            } catch (\Exception $e) {
                DB::rollback();
                return $this->error('Something went wrong', 400, $e->getMessage());
            }
        } else {
            return $this->error('Unauthorized', 403);
        }
    }
}
