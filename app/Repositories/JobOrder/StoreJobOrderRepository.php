<?php

namespace App\Repositories\JobOrder;

use App\Http\Resources\JobOrderResource;
use App\Models\JobOrder;
use App\Models\JobOrderBilling;
use App\Models\JobOrderClient;
use App\Models\JobOrderShipment;
use App\Models\Quotation;
use App\Models\ActivityLog;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class StoreJobOrderRepository extends BaseRepository
{
    public function execute($request){
        $quotation = Quotation::where('reference_number', $request->quotation_reference_number)->first();

        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }
        if ($quotation->as_id !== auth()->id() && !auth()->user()->hasRole('Lead Account Specialist')) {
            return $this->error('You are not authorized to create a Job Order for this quotation', 403);
        }
        if (JobOrder::where('quotation_id', $quotation->id)->exists()) {
            return $this->error('A Job Order has already been created for this quotation', 400);
        }
        if ($quotation->status !== 'ACCEPTED') {
            return $this->error('Job Orders can only be created for quotations in ACCEPTED status', 400);
        }
        
        $idSection = str_pad(((JobOrder::latest('id')->value('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);
        $dateSection = now()->format('m-Y');

        if ($request->job_type === 'LOGISTICS') {
            if ($quotation->regulatoryService) {
                return $this->error('This job type can only be created for logistics quotations', 422);
            }

            $prefix = 'SJO';
            $referenceNumber = "{$prefix}-{$dateSection}-{$idSection}";

            try {
                DB::beginTransaction();

                $jobOrder = JobOrder::create([
                    'reference_number' => $referenceNumber,
                    'job_type' => $request->job_type,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'quotation_id' => $quotation->id,
                    'subject' => $request->subject['subject'],
                    'email_body' => $request->subject['email_body'],
                ]);

                JobOrderClient::create([
                    'job_order_id' => $jobOrder->id,
                    'client_type' => $request->client['client_type'],
                    'accredited' => $request->client['accredited'],
                    'service_type' => $quotation->logisticsService->service_type,
                    'tone_and_attitude' => $request->client['tone_and_attitude'] ?? null,
                    'client_remarks' => $request->client['remarks'] ?? null,
                ]);

                JobOrderShipment::create([
                    'job_order_id' => $jobOrder->id,
                    'service_level' => $request->service['service_level'],
                    'bl_no' => $request->service['bl_no'],
                    'eta' => $request->service['eta'],
                    'etd' => $request->service['etd'],
                    'if_coordinated' => $request->shipment['if_coordinated'] ?? null,
                    'hs_code' => $request->shipment['hs_code'] ?? null,
                    'rod' => $request->shipment['rod'] ?? null,
                    'permits' => $request->shipment['permits'] ?? null,
                    'shipment_remarks' => $request->shipment['special_remarks'] ?? null,
                    'target_delivery_date' => $request->target['delivery_date'] ?? null,
                    'target_completion_date' => $request->target['completion_date'] ?? null,
                    'commitment_remarks' => $request->target['special_remarks'] ?? null,
                ]);

                JobOrderBilling::create([
                    'job_order_id' => $jobOrder->id,
                    'terms_of_payment' => $request->billing['terms_of_payment'] ?? null,
                    'billing_date' => $request->billing['billing_date'] ?? null,
                    'shall_be_billed' => $request->billing['shall_be_billed'] ?? null,
                ]);

                $activityLog = ActivityLog::create([
                    'subject_id' => $jobOrder->id,
                    'subject_type' => JobOrder::class,
                    'user_id' => auth()->id(),
                    'action' => 'Job Order Created',
                ]);

                DB::commit();
                return $this->success('Job Order created successfully', new JobOrderResource($jobOrder), 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Something went wrong' . $e->getMessage(), 500);
            }
        } elseif ($request->job_type === 'REGULATORY') {
            if ($quotation->logisticsService) {
                return $this->error('This job type can only be created for regulatory quotations', 422);
            }

            $prefix = 'SPL';
            $referenceNumber = "{$prefix}-{$dateSection}-{$idSection}";

            try {
                $jobOrder = JobOrder::create([
                    'reference_number' => $referenceNumber,
                    'job_type' => $request->job_type,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'quotation_id' => $quotation->id,
                    'subject' => $request->subject['subject'],
                    'email_body' => $request->subject['email_body'],
                ]);

                JobOrderClient::create([
                    'job_order_id' => $jobOrder->id,
                    'client_type' => $request->client['client_type'],
                    'accredited' => $request->client['accredited'],
                    'service_type' => $request->client['service_type'],
                    'client_remarks' => $request->client['remarks'] ?? null,
                ]);

                $activityLog = ActivityLog::create([
                    'subject_id' => $jobOrder->id,
                    'subject_type' => JobOrder::class,
                    'user_id' => auth()->id(),
                    'action' => 'Job Order Created',
                ]);

                DB::commit();
                return $this->success('Job Order created successfully', new JobOrderResource($jobOrder), 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Something went wrong', $e->getMessage(), 500);
            }
            
        } else {
            return $this->error('Invalid job type', 422);
        }
    }
}
