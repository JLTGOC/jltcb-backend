<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StandardConfigResource extends JsonResource
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
            'template_name' => $this->template_name,
            'policies' => $this->policies,
            'terms_and_conditions' => $this->terms_and_conditions,
            'banking_details' => $this->banking_details,
            'footer' => $this->footer
        ];
    }
}
