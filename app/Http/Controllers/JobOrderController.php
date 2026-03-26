<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    StoreJobOrderRequest,
};
use App\Models\{
    User,
    JobOrder,
    BillingDetails,
    Quotation,
};

class JobOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(JobOrder::class, 'job-orders');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
                    'tone_and_attitude' => $request->client['tone_and_attitude'],
                    'remarks' => $request->client['remarks'],
                    'service_level' => $request->service['service_level'],
                    'bl_no' => $request->service['bl_no'],
                    'eta' => $request->service['eta'],
                    'etd' => $request->service['etd']
                ]);

                $jobOrder->billingDetails()->create([
                    'hs_code' => $request->billing['hs_code'] ?? null,
                    'permits' => $request->billing['permits'] ?? null,
                    'special_remarks' => $request->billing['special_remarks'] ?? null,
                    'terms_of_payment' => $request->billing['terms_of_payment'] ?? null,
                    'billing_date' => $request->billing['billing_date'] ?? null,
                    'shall_be_billed' => $request->billing['shall_be_billed'] ?? null,
                    'closing_remarks' => $request->billing['closing_remarks'] ?? null,
                ]);

            } catch (\Exception $e) {
                return $this->error('Something went wrong' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(JobOrder $jobOrder)
    {
        //
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
}
