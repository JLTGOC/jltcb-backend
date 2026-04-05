<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuotationFieldResource;
use Illuminate\Http\Request;
use App\Models\QuotationField;

class QuotationFieldController extends Controller
{
    /**
     * Index Quotation fields
     * 
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', QuotationField::class);

        $request->validate([
            'type' => 'required|in:REGULATORY,LOGISTICS'
        ]);

        $quotationFields = match ($request->type) {
            'LOGISTICS' => QuotationField::logisticsFields()->get(),
            'REGULATORY' => QuotationField::regulatoryFields()->get(),
        };

        return $this->success(
            'Quotation fields fetched successfully', 
            QuotationFieldResource::collection($quotationFields)
        );
    }
}
