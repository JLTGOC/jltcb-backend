<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    StoreJobOrderRequest,
};
use App\Models\{
    User,
    JobOrder,
    Quotation,
};
use Illuminate\Support\Facades\DB;
use App\Http\Resources\{
    JobOrderResource,
    QuotationResource,
};

class JobOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(JobOrder::class, 'job_order');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole(['Lead Account Specialist'])) {
            $jobOrders = JobOrder::where('shipment_creation_status', 'PENDING')->get();
        } elseif ($user->hasRole('Account Specialist')) {
            $jobOrders = JobOrder::where('as_id', $user->id)->where('shipment_creation_status', 'PENDING')->get();
        } else if ($user->hasRole('Operations')) {
            $jobOrders = JobOrder::where('operations_id', $user->id)->where('shipment_creation_status', 'PENDING')->get();
        } else if ($user->hasRole('Finance')) {
            $jobOrders = JobOrder::where('finance_id', $user->id)->where('shipment_creation_status', 'PENDING')->get();
        } else {
            $jobOrders = JobOrder::where('client_id', $user->id)->where('shipment_creation_status', 'PENDING')->get();
        }

        $jobOrders = $jobOrders->map(function ($j) {
            if ($j->job_type === 'SHIPMENT') {
                $service = 'Logistics Services';
            } elseif ($j->job_type === 'ACCREDITATION') {
                $service = 'Regulatory Services';
            } else {
                $service = 'N/A';
            }

            return [
                'id' => $j->id,
                'reference_number' => $j->reference_number,
                'service' => $service,
                'client' => $j->client->full_name,
                'date_created' => strtoupper($j->created_at->format('F d, Y')),
                'quotation_id' => $j->quotation_id,
                'assigned_to' => $j->operations_id ?? 'Available'
            ];
        });

        return $this->success('Job Orders fetched successfully', $jobOrders, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJobOrderRequest $request)
    {
        if ($request->job_type === 'SHIPMENT') {
            $quotation = Quotation::where('reference_number', $request->quotation_reference_number)->first();
            if (!$quotation) {
                return $this->error('Quotation not found', 404);
            }
            if ($quotation->as_id !== auth()->id()) {
                return $this->error('You are not authorized to create a Job Order for this quotation', 403);
            }
            if ($quotation->regulatoryService) {
                return $this->error('Job Orders can only be created for logistics quotations', 422);
            }

            if (JobOrder::where('quotation_id', $quotation->id)->exists()) {
                return $this->error('A Job Order has already been created for this quotation', 400);
            }

            $idSection = str_pad(((JobOrder::latest('id')->value('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);
            $prefix = 'SJO';
            $dateSection = now()->format('m-Y');

            $referenceNumber = "{$prefix}-{$dateSection}-{$idSection}";

            $previousClientJobOrder = JobOrder::where('client_id', $request->client_id)->latest()->first() ?? null;
            if ($previousClientJobOrder) {
                $operationsId = $previousClientJobOrder->operations_id;
            } else {
                $ops = User::role('Operations')->get();
                foreach ($ops as $op) {
                    $ops->count = JobOrder::where('operations_id', $op->id)->count();
                }
                $leastLoadedOps = $ops->sortBy('count')->first();
                $operationsId = $leastLoadedOps->id;
            }

            try {
                $jobOrder = JobOrder::create([
                    'reference_number' => $referenceNumber,
                    'job_type' => $request->job_type,
                    'client_id' => $quotation->client_id,
                    'as_id' => $quotation->as_id,
                    'operations_id' => $operationsId,
                    'quotation_id' => $quotation->id,
                    'subject' => $request->subject['subject'],
                    'email_body' => $request->subject['email_body'],
                    'client_type' => $request->client['client_type'],
                    'accredited' => $request->client['accredited'],
                    'tone_and_attitude' => $request->client['tone_and_attitude'] ?? null,
                    'client_remarks' => $request->client['remarks'] ?? null,
                    'service_level' => $request->service['service_level'],
                    'bl_no' => $request->service['bl_no'],
                    'eta' => $request->service['eta'],
                    'etd' => $request->service['etd'],
                    'hs_code' => $request->shipment['hs_code'] ?? null,
                    'rod' => $request->shipment['rod'] ?? null,
                    'permits' => $request->shipment['permits'] ?? null,
                    'shipment_remarks' => $request->shipment['special_remarks'] ?? null,
                    'target_delivery_date' => $request->target['target_delivery_date'] ?? null,
                    'target_completion_date' => $request->target['target_completion_date'] ?? null,
                    'commitment_remarks' => $request->target['special_remarks'] ?? null,
                    'terms_of_payment' => $request->billing['terms_of_payment'] ?? null,
                    'billing_date' => $request->billing['billing_date'] ?? null,
                    'shall_be_billed' => $request->billing['shall_be_billed'] ?? null,
                ]);

                DB::commit();
                return $this->success('Job Order created successfully', new JobOrderResource($jobOrder), 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Something went wrong' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(JobOrder $jobOrder)
    {
        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        return $this->success('Job Order fetched successfully', new JobOrderResource($jobOrder), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobOrder $jobOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobOrder $jobOrder)
    {
        //
    }

    public function jobOrderEnums() {
        $this->authorize('jobOrderEnums', JobOrder::class);
        
        $clientTypes = ['NEW', 'RENEWAL'];
        $accredited = ['REGULAR', 'EXPEDITED'];
        $serviceLevels = [
            'CARGO CONSOLIDATION (CC)',
            'DIRECT EXPORT (DE)',
            'INTERNATIONAL FREIGHT FORWARDING (IFF)',
            'CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
            'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC)',
            'INTERNATIONAL FREIGHT FORWARDING (IFF), CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
        ];
        $shallBeBilled = [
            'AS PER QUOTE',
            'AS PER RECEIPT',
            'THIRD-PARTY RECEIPTED CHARGES ADVANCES, DEBIT NOTE, CHARGES UPON DELIVERY',
            'CARGO CONSOLIDATION (CC), DIRECT EXPORT (DE)',
            'UPON SERVICE RENDERED (COD)'
        ];

        return $this->success('Job Order Enums fetched successfully', [
            'client_types' => $clientTypes,
            'accredited' => $accredited,
            'service_levels' => $serviceLevels,
            'shall_be_billed' => $shallBeBilled,
        ]);
    }

    public function showJobOrderQuotation(JobOrder $jobOrder) {
        if (!$jobOrder) {
            return $this->error('Job Order not found', 404);
        }

        $quotation = $jobOrder->quotation;

        if (!$quotation) {
            return $this->error('Quotation not found for this Job Order', 404);
        }

        return $this->success('Quotation fetched successfully', new QuotationResource($quotation), 200);
    }
}
