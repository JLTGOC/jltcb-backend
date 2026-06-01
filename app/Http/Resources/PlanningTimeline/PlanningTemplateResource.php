<?php

namespace App\Http\Resources\PlanningTimeline;

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
            'services' => $this->service_type,
            'is_active' => $this->is_active,

            'config' => $this->when(
                $this->relationLoaded('configPhases')
                && $this->relationLoaded('configProcesses')
                && $this->relationLoaded('configTasks'),
                fn () => [
                    'phases'    => $this->configPhases->map(fn ($phase) => [
                        'id'         => $phase->id,
                        'name'       => $phase->name,
                        'sort_order' => $phase->sort_order,
                    ]),
                    'processes' => $this->configProcesses->map(fn ($process) => [
                        'id'         => $process->id,
                        'name'       => $process->name,
                    ]),
                    'tasks'     => $this->configTasks->map(fn ($task) => [
                        'id'         => $task->id,
                        'name'       => $task->name,
                    ]),
                ]
            ),

            'structure' => TemplatePhaseResource::collection($this->whenLoaded('phases')),
        ];
    }
}
