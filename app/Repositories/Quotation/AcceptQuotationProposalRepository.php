<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Repositories\BaseRepository;
use App\Models\QuotationHistory;

class AcceptQuotationProposalRepository extends BaseRepository
{
    public function execute($quotation){
        $quotation->update([
            'status' => 'ACCEPTED',
        ]);

        $activityLog = QuotationHistory::create([
            'user_id' => auth()->id(),
            'quotation_id' => $quotation->id,
            'action' => 'Quotation Accepted By Client',
        ]);

        return $this->success('Quotation accepted successfully', new QuotationResource($quotation), 200);
    }
}
