<?php

namespace App\Repositories\Quotation;

use App\Models\ReassignmentRequest;
use App\Repositories\BaseRepository;

class RequestReassignmentRepository extends BaseRepository
{
    public function execute($quotation, $request){
        if (auth()->user()->id !== $quotation->as_id) {
            return $this->error('Only the assigned Account Specialist can request for reassignment', 403);
        }

        $reassignmentRequest = ReassignmentRequest::where('quotation_id', $quotation->id)->where('status', 'PENDING')->latest()->first();

        if ($reassignmentRequest) {
            return $this->error('A reassignment request is already pending for this quotation', 422);
        } else {
            $request->validate([
                'reason' => ['required', 'string', 'in:WORKLOAD,EMERGENCY / LEAVE,CLIENT REQUEST'],
                'additional_details' => ['nullable', 'string']
            ]);

            $quotation->update([
                'assignment_status' => 'REASSIGNMENT REQUESTED',
            ]);

            $reassignmentRequest = ReassignmentRequest::create([
                'quotation_id' => $quotation->id,
                'as_id' => auth()->id(),
                'reason' => $request->reason,
                'additional_details' => $request->additional_details,
                'status' => 'PENDING'
            ]);

            return $this->success('Reassignment request submitted successfully', $reassignmentRequest, 200);
        }
    }
}
