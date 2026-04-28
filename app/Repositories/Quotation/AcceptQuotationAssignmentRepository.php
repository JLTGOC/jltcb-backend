<?php

namespace App\Repositories\Quotation;

use App\Repositories\BaseRepository;
use App\Http\Resources\QuotationResource;
use Carbon\Carbon;

class AcceptQuotationAssignmentRepository extends BaseRepository
{
    public function execute($quotation){
        if ($quotation->assignment_status !== 'AVAILABLE') {
            return $this->error('This quotation is not available for acceptance', 422);
        }

        $quotation->update([
            'as_id' => auth()->id(),
            'assignment_status' => 'ASSIGNED',
            'assigned_at' => Carbon::now()
        ]);

        return $this->success('Quotation assignment accepted successfully', new QuotationResource($quotation), 200);
    }
}
