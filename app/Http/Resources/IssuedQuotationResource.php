<?php

namespace App\Http\Resources;

use App\Models\Quotation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use function Illuminate\Support\minutes;
use function Symfony\Component\Clock\now;

class IssuedQuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_id' => $this->quotation_id,
            'template_id' => $this->template_id,
            'issued_by' => User::find($this->issued_by)?->full_name ?? null,
            'subject' => $this->subject,
            'message' => $this->message,

            'quotation_details' => $this->whenLoaded('detailValues', function () {
                return $this->detailValues->map(function ($detail) {
                    return [
                        'label' => $detail->label,
                        'value' => $detail->value,
                    ];
                }); 
            }),

            'billing_details' => [
                'charges' => $this->whenLoaded('charges', function () {
                    return $this->charges->map(function ($charge) {
                        return [
                            'name' => $charge->name,
                            'subtotal' => $charge->subtotal,
                            'items' => $charge->relationLoaded('items')
                                ? $charge->items->map(fn($item) => [
                                    'receipt_charge_label' => $item->receipt_charge_label,
                                    'currency_label' => $item->currency_label,
                                    'uom_label' => $item->uom_label,
                                    'amount' => $item->amount,
                                ])
                                : null,
                        ];
                    });
                }),
                'total' => $this->whenLoaded(
                    'charges',
                    fn() => $this->charges->sum('subtotal')
                ),
            ],

            'standard_config' => $this->whenLoaded('standardConfig', function () {
                return [
                    'name' => $this->standardConfig->name,
                    'policies' => $this->standardConfig->policies,
                    'terms_and_conditions' => $this->standardConfig->terms_and_conditions,
                    'banking_details' => $this->standardConfig->banking_details,
                    'footer' => $this->standardConfig->footer,
                ];
            }),

            'signatory' => $this->whenLoaded('authorizedSignatory', function () {
                return [
                    'closing_statement' => $this->authorizedSignatory->closing_statement,
                    'is_authorized_signatory' => $this->authorizedSignatory->is_authorized_signatory,
                    'authorized_signatory_name' => $this->authorizedSignatory->authorized_signatory_name,
                    'position' => $this->authorizedSignatory->position,
                    'signature_file_path' => URL::temporarySignedRoute(
                        'signatures.view', 
                        Carbon::now()->addMinutes(5),
                        [
                            'id' => $this->authorizedSignatory->id
                        ]),
                ];
            }),

            'client_inputs' => $this->whenLoaded('template', function () {
                return new ClientInputResource($this->template, $this->quotation_id);
            }),

            'quotation_file' => $this->whenLoaded('quotation', function() {
                $file = $this->quotation->files()
                    ->where('type', 'PROPOSAL')
                    ->first();

                if (!$file) {
                    return null;
                }

                return URL::temporarySignedRoute('files.view', Carbon::now()->addMinutes(10), [
                    'file' => $file->id
                ]);
            })            
        ];
    }
}
