<?php

namespace App\Http\Resources\PlanningTimeline\Template;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanningPhaseHeadingResource extends JsonResource
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
            'template_phase_id' => $this->template_phase_id,
            'name' => $this->name,
            'input_type' => $this->input_type,
            'sort_order' => $this->sort_order,
            'is_default' => $this->isDefault(),
        ];
    }
}
