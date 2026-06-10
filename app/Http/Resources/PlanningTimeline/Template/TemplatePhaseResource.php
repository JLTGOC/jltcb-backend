<?php

namespace App\Http\Resources\PlanningTimeline\Template;

use App\Http\Resources\PlanningTimeline\Template\PlanningPhaseHeadingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplatePhaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'config_phase_id' => $this->config_phase_id,
            'name'            => $this->configPhase->name,
            'sort_order'      => $this->sort_order,
            'headings'        => PlanningPhaseHeadingResource::collection($this->whenLoaded('headings')),
            'processes'       => TemplatePhaseProcessResource::collection(
                $this->whenLoaded('processes')
            ),
        ];
    }
}
