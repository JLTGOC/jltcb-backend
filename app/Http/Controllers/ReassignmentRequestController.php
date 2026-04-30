<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    ReassignmentRequest,
    User
};

class ReassignmentRequestController extends Controller
{
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
    public function store(Request $request)
    {
        //
    }

    /** 
     * Show Reassignment Request
     * 
     * Display the specified resource.
     */
    public function show(ReassignmentRequest $reassignmentRequest)
    {
        if (!$reassignmentRequest) {
            return $this->error('Reassignment request not found', 404);
        }

        if ($reassignmentRequest->quotation) {
            $data = [
                'id' => $reassignmentRequest->id,
                'quotation_reference_number' => $reassignmentRequest->quotation ? $reassignmentRequest->quotation->reference_number : null,
                'account_specialist' => $reassignmentRequest->accountSpecialist ? 
                    mb_strtoupper($reassignmentRequest->accountSpecialist->username) . ' ' . $reassignmentRequest->accountSpecialist->full_name :
                    null,
                'reason' => $reassignmentRequest->reason,
                'additional_details' => $reassignmentRequest->additional_details,
                'status' => $reassignmentRequest->status,
            ];
        } elseif ($reassignmentRequest->jobOrder) {
            $data = [
                'id' => $reassignmentRequest->id,
                'job_order_reference_number' => $reassignmentRequest->jobOrder ? $reassignmentRequest->jobOrder->reference_number : null,
                'operations' => $reassignmentRequest->operations ? mb_strtoupper($reassignmentRequest->operations->username) . ' ' . $reassignmentRequest->operations->full_name : null,
                'reason' => $reassignmentRequest->reason,
                'additional_details' => $reassignmentRequest->additional_details,
                'status' => $reassignmentRequest->status,
            ];
        } else {
            return $this->error('Associated quotation or job order not found for this reassignment request', 404);
        }
        

        return $this->success('Reassignment request retrieved successfully', $data, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReassignmentRequest $reassignmentRequest)
    {
        //
    }

    /**
     * Get Reassignment Request Enums
     *
     * This method returns the enum options for reassignment requests, such as reasons for reassignment. 
     */
    public function enums(Request $request) {
        $this->authorize('enums', ReassignmentRequest::class);

        $reassignmentReasons = [];
        $accountSpecialists = [];
        $operations = [];

        $request->validate([
            'reasons' => 'sometimes',
            'as' => 'sometimes',
            'ops' => 'sometimes',
        ]);
        
        if ($request->has('reasons')) {
            $reassignmentReasons = ['WORKLOAD', 'EMERGENCY / LEAVE', 'CLIENT REQUEST'];
        }
        if ($request->has('as')) {
            $accountSpecialists = User::role('Account Specialist')->get(['id', 'username', 'full_name']);
        }
        if ($request->has('ops')) {
            $operations = User::role('Operations')->get(['id', 'username', 'full_name']);
        }

        return $this->success('Reassignment request enums retrieved successfully', [
            'reassignment_reasons' => $reassignmentReasons,
            'account_specialists' => $accountSpecialists,
            'operations' => $operations
        ], 200);
    }

    /**
     * Cancel Reassignment Request
     * 
     * This method allows the requester to cancel a pending reassignment request. Only the user who created the request can cancel it, and only if the request is still pending.
     */
    public function cancel(Request $request, ReassignmentRequest $reassignmentRequest) {
        $this->authorize('cancel', $reassignmentRequest);

        if ($reassignmentRequest->status !== 'PENDING') {
            return $this->error('Only pending reassignment requests can be cancelled', 422);
        }

        $quotation = $reassignmentRequest->quotation;

        $reassignmentRequest->update([
            'status' => 'CANCELLED'
        ]);

        $quotation->update([
            'assignment_status' => 'ASSIGNED',
        ]);

        return $this->success('Reassignment request cancelled successfully', $reassignmentRequest, 200);
    }
}
