<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\ClientInputResource;
use App\Models\QuotationTemplate\QuotationTemplate;
use App\Repositories\BaseRepository;

class ClientInputsRepository extends BaseRepository
{
    public function execute($quotation, $request)
    {
        $validated = $request->validated();

        $template = QuotationTemplate::find($validated['template_id']);

        return $this->success(
            'Template based client inputs fetched successfully',
            new ClientInputResource($template, $quotation->id)
        );

    }
}
