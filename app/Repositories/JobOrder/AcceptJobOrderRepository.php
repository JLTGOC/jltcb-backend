<?php

namespace App\Repositories\JobOrder;

use App\Http\Resources\JobOrderResource;
use App\Models\QuotationHistory;
use App\Repositories\BaseRepository;
use Carbon\Carbon;

class AcceptJobOrderRepository extends BaseRepository
{
    public function execute($jobOrder){
        $user = auth()->user();
        $jobOrder->update([
            'operations_id' => $user->id,
            'assignment_status' => 'ASSIGNED',
            'assigned_at' => Carbon::now(),
        ]);

        if ($jobOrder->latestReassignmentRequest) {
            $jobOrder->latestReassignmentRequest->update([
                'status' => 'APPROVED',
            ]);
        }

        $activityLog = QuotationHistory::create([
            'quotation_id' => $jobOrder->quotation->id,
            'user_id' => auth()->id(),
            'action' => 'Job Order Accepted',
        ]);

        return $this->success('Job Order accepted successfully', new JobOrderResource($jobOrder), 200);
    }
}
