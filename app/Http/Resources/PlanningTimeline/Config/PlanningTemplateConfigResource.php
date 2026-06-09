<?php

namespace App\Http\Resources\PlanningTimeline\Config;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanningTemplateConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'version_number' => $this->version_number,
            'phases' => PlanningConfigPhaseResource::collection($this->whenLoaded('phases')),
            'processes' => PlanningConfigProcessResource::collection(
                $this->whenLoaded('processes')
            ),
            'tasks' => PlanningConfigTaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
