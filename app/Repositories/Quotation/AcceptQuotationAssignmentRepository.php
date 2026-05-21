<?php

namespace App\Repositories\Quotation;

use App\Repositories\BaseRepository;
use App\Http\Resources\QuotationResource;
use Carbon\Carbon;
use App\Models\ReassignmentRequest;
use App\Models\ActivityLog;

class AcceptQuotationAssignmentRepository extends BaseRepository
{
    public function execute($quotation){
        $quotation->update([
            'as_id' => auth()->id(),
            'assignment_status' => 'ASSIGNED',
            'assigned_at' => Carbon::now()
        ]);
        
        if ($quotation->latestReassignmentRequest) {
            $quotation->latestReassignmentRequest->update([
                'status' => 'APPROVED',
            ]);
        }

        $activityLog = ActivityLog::create([
            'subject_id' => $quotation->id,
            'subject_type' => Quotation::class,
            'user_id' => auth()->id(),
            'action' => 'Quotation Assignment Accepted',
        ]);

        return $this->success('Quotation assignment accepted successfully', new QuotationResource($quotation), 200);
    }
}
