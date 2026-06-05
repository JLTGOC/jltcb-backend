<?php

namespace App\Http\Resources\PlanningTimeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanningConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'version_id' => $this->id,
            'phases' => $this->whenLoaded('phases', function() {
                return $this->phases->map(function($phase) {
                    return [
                        'id' => $phase->id,
                        'name' => $phase->name
                    ];
                });
            }),
            'processes' => $this->whenLoaded('processes', function() {
                return $this->processes->map(function($process) {
                    return [
                        'id' => $process->id,
                        'name' => $process->name
                    ];
                });
            }),
            'tasks' => $this->whenLoaded('tasks', function() {
                return $this->tasks->map(function($tasks) {
                    return [
                        'id' => $tasks->id,
                        'name' => $tasks->name
                    ];
                });
            })
        ];
    }
}
