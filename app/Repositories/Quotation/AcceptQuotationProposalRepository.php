<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Repositories\BaseRepository;
use App\Models\ActivityLog;

class AcceptQuotationProposalRepository extends BaseRepository
{
    public function execute($quotation){
        $quotation->update([
            'status' => 'ACCEPTED',
        ]);

        $activityLog = ActivityLog::create([
            'subject_id' => $quotation->id,
            'subject_type' => Quotation::class,
            'user_id' => auth()->id(),
            'action' => 'Quotation Accepted By Client',
        ]);

        return $this->success('Quotation accepted successfully', new QuotationResource($quotation), 200);
    }
}
