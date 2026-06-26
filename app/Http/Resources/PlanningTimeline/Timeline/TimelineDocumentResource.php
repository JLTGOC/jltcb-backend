<?php

namespace App\Http\Resources\PlanningTimeline\Timeline;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class TimelineDocumentResource extends JsonResource
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
            'type' => $this->type,
            'file_type' => $this->file_type,
            'status' => $this->status,
            'uploaded_by' => [
                'name' => $this->uploadedBy?->full_name,
                'image_path' => asset(Storage::url($this->uploadedBy?->image_path)),
            ],
            'uploaded_on' => $this->created_at,
            'file_url' => URL::temporarySignedRoute('timeline-documents.view', Carbon::now()->addMinutes(10), [
                'document' => $this->id
            ]),
        ];
    }
}
