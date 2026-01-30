<?php

namespace App\Traits;

use App\Models\Quotation;

trait Generator
{
    protected function quotationReferenceNumber()
    {
        do {

            $referenceNumber = bin2hex(random_bytes(8));
        } while (Quotation::where("reference_number", $referenceNumber)->first());

        return $referenceNumber;
    }
}
