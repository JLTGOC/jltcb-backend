<?php

namespace App\Repositories\Quotation;

use App\Repositories\BaseRepository;

class DestroyQuotationRepository extends BaseRepository
{
    public function execute($quotation){
        $quotation->delete();

        return $this->success('Quotation deleted', [], 200);
    }
}
