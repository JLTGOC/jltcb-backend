<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\ClientInputResource;
use App\Models\QuotationTemplate;
use App\Repositories\BaseRepository;

class ClientInputsRepository extends BaseRepository
{
    public function execute($quotation, $request)
    {
        $request->validate([
            'template_id' => [
                'required',
                'integer',
                'exists:quotation_templates,id',
                function ($attribute, $value, $fail) use ($quotation) {
                    if ($quotation->regulatoryService) {
                        $type = 'REGULATORY';
                    } elseif ($quotation->logisticsService) {
                        $type = 'LOGISTICS';
                    } else {
                        return;
                    }

                    $template = QuotationTemplate::find($value);

                    if (!$template) {
                        return;
                    }

                    $quotationField = $template->quotationFields()->first();

                    if (!$quotationField || $quotationField->quotation_type !== $type) {
                        $fail('The template id is not compatible with this quotation');
                    }
                },
            ],
        ]);

        $template = QuotationTemplate::find($request->template_id);

        return $this->success(
            'Template based client inputs fetched successfully',
            new ClientInputResource($template, $quotation->id)
        );

    }
}
