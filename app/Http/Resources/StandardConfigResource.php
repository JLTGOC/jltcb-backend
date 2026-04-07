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
        $data = [
            'id' => $this->id,
            'template_name' => $this->template_name,
        ];

        if ($request->routeIs('standard-templates.show')) {
            $data['policies'] = $this->policies;
            $data['terms_and_conditions'] = $this->terms_and_conditions;
            $data['banking_details'] = $this->banking_details;
            $data['footer'] = $this->footer;
        }
        
        return $data;
    }
}
