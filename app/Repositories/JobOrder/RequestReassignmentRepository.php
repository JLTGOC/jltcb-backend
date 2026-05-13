<?php

namespace App\Repositories\JobOrder;

use App\Models\QuotationHistory;
use App\Models\ReassignmentRequest;
use App\Repositories\BaseRepository;

class RequestReassignmentRepository extends BaseRepository
{
    public function execute($jobOrder, $request){
        $reassignmentRequest = ReassignmentRequest::where('job_order_id', $jobOrder->id)->where('status', 'PENDING')->latest()->first();

        if ($reassignmentRequest) {
            return $this->error('A reassignment request is already pending for this Job Order', 422);
        } else {
            $jobOrder->update([
                'assignment_status' => 'REASSIGNMENT REQUESTED',
            ]);

            $reassignmentRequest = ReassignmentRequest::create([
                'job_order_id' => $jobOrder->id,
                'as_id' => auth()->id(),
                'reason' => $request->reason,
                'additional_details' => $request->additional_details,
                'status' => 'PENDING',
            ]);

            $activityLog = QuotationHistory::create([
                'quotation_id' => $jobOrder->quotation->id,
                'user_id' => auth()->id(),
                'action' => 'Reassignment Requested',
            ]);

            return $this->success('Reassignment request submitted successfully', $reassignmentRequest, 200);
        }
    }
}
