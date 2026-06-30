<?php

namespace App\Http\Resources\PlanningTimeline\Timeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
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
            'reference_number' => $this->when($this->relationLoaded('jobOrder'), $this->jobOrder->reference_number),
            'service_data' => $this->getServiceData(),
            'phases' => TimelinePhaseResource::collection($this->whenLoaded('phases')),
        ];
    }

    private function getServiceData(): ?array
        {
            $jobOrder = $this->jobOrder;

            if (!$jobOrder) {
                return null;
            }

            if ($jobOrder->job_type === 'LOGISTICS') {
                return [
                    'transport_mode' => $jobOrder?->quotation?->logisticsService?->transport_mode,
                    'origin' => $jobOrder?->quotation?->logisticsService?->origin,
                    'destination' => $jobOrder?->quotation?->logisticsService?->destination,
                ];
            }

            if ($jobOrder->job_type === 'REGULATORY') {
                return [
                    // regulatory specific timeline data
                ];
            }

            return null;
        }
}
