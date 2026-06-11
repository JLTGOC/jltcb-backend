<?php

namespace App\Http\Resources\PlanningTimeline\Template;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplatePhaseProcessResource extends JsonResource
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
            'config_process_id' => $this->config_process_id,
            'name' => $this->configProcess->name,
            'tasks' => TemplateProcessTaskResource::collection(
                $this->whenLoaded('tasks')
            ),
        ];
    }
}
