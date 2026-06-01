<?php

namespace App\Http\Resources\PlanningTimeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateProcessTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'config_task_id' => $this->config_task_id,
            'name'           => $this->configTask->name,
        ];
    }
}
