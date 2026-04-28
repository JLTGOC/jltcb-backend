<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Repositories\BaseRepository;

class ShowQuotationRepository extends BaseRepository
{
    public function execute($quotation){
        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }

        $quotationCollection = new QuotationResource($quotation);

        return $this->success('Quotation details fetched successfully', $quotationCollection, 200);
    }
}
