<?php

namespace App\Http\Resources;

use App\Models\QuotationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\RegulatoryService;
use App\Models\LogisticsService;

class ClientInputResource extends JsonResource
{
    public function __construct(
        $resource,
        protected int $quotationId
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('quotationFields');

        $model = match($this->resource->service_type) {
            'REGULATORY' => RegulatoryService::class,
            'LOGISTICS'  => LogisticsService::class,
        };

        $quotationFields = $this->resource->quotationFields()->pluck('field_name')->toArray();
        $clientData = $model::where('quotation_id', $this->quotationId)
            ->select($quotationFields)
            ->first();

        return $this->resource->quotationFields->map(fn($quoteField) => [
                'label' => $quoteField->display_name,
                'value' => $clientData?->{$quoteField->field_name},
            ])->toArray();
    }
}
