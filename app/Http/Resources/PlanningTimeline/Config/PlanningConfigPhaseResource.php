<?php

namespace App\Http\Resources\PlanningTimeline\Config;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PlanningTimeline\PlanningTemplateResource;

class PlanningConfigPhaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $templates = $this->templatePhases
            ->map(fn($templatePhase) => $templatePhase->template)
            ->filter()       
            ->unique('id')    
            ->values();      

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_locked' => $this->is_locked,
            'used_by_templates' => PlanningTemplateResource::collection($templates),
        ];
    }
}
