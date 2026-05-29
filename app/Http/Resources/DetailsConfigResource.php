<?php

namespace App\Http\Resources;

use App\Http\Resources\ConfigDropdownOptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailsConfigResource extends JsonResource
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
            'label' => $this->label,
            'type' => $this->type,
            'dropdown_options' => $this->when(
                $this->type === 'DROPDOWN', 
                ConfigDropdownOptionResource::collection($this->whenLoaded('dropdownOptions'))
            ), 
            'count' => $this->when(
                $this->type === 'DROPDOWN' && $this->relationLoaded('dropdownOptions'),
                fn () => $this->dropdownOptions->count()
            ),
        ];
    }
}
