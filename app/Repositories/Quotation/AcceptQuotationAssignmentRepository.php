<?php

namespace App\Repositories\Quotation;

use App\Repositories\BaseRepository;
use App\Http\Resources\QuotationResource;
use Carbon\Carbon;
use App\Models\ReassignmentRequest;

class AcceptQuotationAssignmentRepository extends BaseRepository
{
    public function execute($quotation){
        if ($quotation->assignment_status !== 'AVAILABLE' || $quotation->assignment_status !== 'REASSIGNMENT REQUESTED') {
            return $this->error('This quotation is not available for acceptance', 422);
        } elseif ($quotation->assignment_status === 'REASSIGNMENT REQUESTED') {
            $reassignmentRequest = ReassignmentRequest::where('quotation_id', $quotation->id)->where('status', 'PENDING')->latest()->first();

            if (!$reassignmentRequest) {
                return $this->error('No pending reassignment request for this quotation', 422);
            }

            $reassignmentRequest->update([
                'status' => 'APPROVED'
            ]);
        }

        $quotation->update([
            'as_id' => auth()->id(),
            'assignment_status' => 'ASSIGNED',
            'assigned_at' => Carbon::now()
        ]);

        return $this->success('Quotation assignment accepted successfully', new QuotationResource($quotation), 200);
    }
}
