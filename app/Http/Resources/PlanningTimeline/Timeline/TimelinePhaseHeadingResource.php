<?php

namespace App\Http\Resources\PlanningTimeline\Timeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelinePhaseHeadingResource extends JsonResource
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
            'input_type' => $this->input_type,
            'sort_order' => $this->sort_order,
            'key' => $this->key,
        ];
    }
}
