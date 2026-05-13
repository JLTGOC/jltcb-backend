<?php

namespace App\Repositories\JobOrder;

use App\Http\Resources\QuotationResource;
use App\Repositories\BaseRepository;

class ShowJobOrderQuotationRepository extends BaseRepository
{
    public function execute($jobOrder){
        $quotation = $jobOrder->quotation;

        if (!$quotation) {
            return $this->error('Quotation not found for this Job Order', 404);
        }

        return $this->success('Quotation fetched successfully', new QuotationResource($quotation), 200);
    }
}
