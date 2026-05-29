<?php

namespace App\Repositories\JobOrder;

use App\Models\ActivityLog;
use App\Models\ReassignmentRequest;
use App\Models\User;
use App\Repositories\BaseRepository;
use Carbon\Carbon;

class ReassignOpsRepository extends BaseRepository
{
    public function execute($request, $jobOrder){
        $reassignmentRequest = ReassignmentRequest::where('job_order_id', $jobOrder->id)->where('status', 'PENDING')->latest()->first();

        if (!$reassignmentRequest) {
            return $this->error('No pending reassignment request for this Job Order', 422);
        }

        if ($request->status === 'REJECTED') {
            $jobOrder->update([
                'assignment_status' => 'ASSIGNED',
            ]);

            $reassignmentRequest->update([
                'status' => 'REJECTED',
            ]);

            $activityLog = ActivityLog::create([
                'subject_id' => $jobOrder->id,
                'subject_type' => JobOrder::class,
                'user_id' => auth()->id(),
                'action' => 'Reassignment Request Rejected',
            ]);

            return $this->success('Reassignment request rejected', $reassignmentRequest, 200);
        } elseif ($request->status === 'APPROVED') {
            $user = User::find($request->operations_id);
            
            if (!$user || !$user->hasRole(['Operations', 'Lead Operations', 'Client Success', 'Lead Client Success'])) {
                return $this->error('The selected user is not an Operations user', 422);
            }
            if ((int) $request->operations_id === $jobOrder->operations_id) {
                return $this->error('The Job Order is already assigned to this Operations user', 422);
            }

            $jobOrder->update([
                'operations_id' => $request->operations_id,
                'assigned_at' => Carbon::now(),
                'assignment_status' => 'ASSIGNED'
            ]);

            $reassignmentRequest->update([
                'status' => 'APPROVED',
            ]);

            $activityLog = ActivityLog::create([
                'subject_id' => $jobOrder->id,
                'subject_type' => JobOrder::class,
                'user_id' => auth()->id(),
                'action' => 'Reassignment Request Approved',
            ]);

            return $this->success('Reassignment request approved', $reassignmentRequest, 200);
        }
    }
}
