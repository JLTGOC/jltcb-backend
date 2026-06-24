<?php

namespace App\Http\Resources\PlanningTimeline\Timeline;

use App\Http\Resources\PlanningTimeline\Timeline\TimelinePhaseHeadingResource;
use App\Http\Resources\PlanningTimeline\Timeline\TimelineTaskAssigneeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TimelinePhaseResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'headings' => TimelinePhaseHeadingResource::collection($this->whenloaded('headings')),
            'processes' => TimelineProcessResource::collection($this->whenLoaded('processes'))
        ];
    }
}
