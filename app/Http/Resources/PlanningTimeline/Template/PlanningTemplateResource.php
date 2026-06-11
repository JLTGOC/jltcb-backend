<?php

namespace App\Http\Resources\PlanningTimeline\Template;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanningTemplateResource extends JsonResource
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
            'name' => $this->name,
            'version_number' => $this->version_number,
            'service_type' => $this->serviceType->name,
            'service_category' => $this->service_category,
            'is_active' => $this->is_active,
            'phases' => TemplatePhaseResource::collection($this->whenLoaded('phases')),
        ];
    }
}
