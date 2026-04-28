<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Repositories\BaseRepository;

class AcceptQuotationProposalRepository extends BaseRepository
{
    public function execute($quotation, $request){
        $quotation->update([
            'status' => 'ACCEPTED',
        ]);

        return $this->success('Quotation accepted successfully', new QuotationResource($quotation), 200);
    }
}
