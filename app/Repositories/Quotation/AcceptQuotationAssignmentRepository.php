<?php

namespace App\Repositories\Quotation;

use App\Repositories\BaseRepository;
use App\Http\Resources\QuotationResource;
use Carbon\Carbon;
use App\Models\ReassignmentRequest;

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

        return $this->success('Quotation assignment accepted successfully', new QuotationResource($quotation), 200);
    }
}
