<?php

namespace App\Repositories\Quotation;

use App\Models\ReassignmentRequest;
use App\Models\QuotationHistory;
use App\Repositories\BaseRepository;

class RequestReassignmentRepository extends BaseRepository
{
    public function execute($quotation, $request){
        if (auth()->user()->id !== $quotation->as_id) {
            return $this->error('Only the assigned Account Specialist can request for reassignment', 403);
        }

        $validated = $request->validated();

        $reassignmentRequest = ReassignmentRequest::where('quotation_id', $quotation->id)->where('status', 'PENDING')->latest()->first();

        if ($reassignmentRequest) {
            return $this->error('A reassignment request is already pending for this quotation', 422);
        }

        $quotation->update([
            'assignment_status' => 'REASSIGNMENT REQUESTED',
        ]);

        $reassignmentRequest = ReassignmentRequest::create([
            'quotation_id' => $quotation->id,
            'as_id' => auth()->id(),
            'reason' => $validated['reason'],
            'additional_details' => $validated['additional_details'] ?? null,
            'status' => 'PENDING'
        ]);

        $activityLog = QuotationHistory::create([
            'user_id' => auth()->id(),
            'quotation_id' => $quotation->id,
            'action' => 'Reassignment Requested',
        ]);

        return $this->success('Reassignment request submitted successfully', $reassignmentRequest, 200);
    }
}
