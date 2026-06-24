<?php

namespace App\Http\Resources\PlanningTimeline\Timeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineTaskResource extends JsonResource
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
            'is_complete' => $this->is_complete,
            'values' => $this->when(
                $this->relationLoaded('values') && $this->relationLoaded('assignees'),
                fn() => $this->values->mapWithKeys(
                    function($value) {
                        if ($value->phaseHeading->key === 'pic') {
                            $assignees = $this->assignees->map(function($assignee) {
                                return [
                                    'user_id' => $assignee->id,
                                    'name' => $assignee->full_name
                                ];
                            });

                            return [(string) $value->timeline_phase_heading_id => $assignees];
                        }
                        return [(string) $value->timeline_phase_heading_id => $value->value];
                    } 
                )
            ),
        ];
    }
}
