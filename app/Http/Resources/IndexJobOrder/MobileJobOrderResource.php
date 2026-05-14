<?php

namespace App\Http\Resources\IndexJobOrder;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\JobOrder;
use App\Models\IssuedQuotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MobileJobOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->job_type === 'LOGISTICS') {
            $service = 'Logistics Services';
        } elseif ($this->job_type === 'REGULATORY') {
            $service = 'Regulatory Services';
        } else {
            $service = 'N/A';
        }

        $assignedTo = $this->operations ? $this->operations->username : 'Available';

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'service' => $service,
            'client' => $this->client->full_name,
            'client_type' => JobOrder::where('client_id', $this->client_id)->count() > 1 ? 'OLD' : 'NEW',
            'date_created' => strtoupper($this->created_at->format('F d, Y')),
            'quotation_id' => $this->quotation_id,
            'quotation_reference_number' => $this->quotation->reference_number,
            'assigned_to' => $assignedTo,
            'ops_image' => $this->operations ? asset(Storage::url($this->operations->image_path)) : null,
            'reassignment_request_id' => $this->latestReassignmentRequest?->status !== 'PENDING' ? null : $this->latestReassignmentRequest->id,
            'requested_at' => $this->latestReassignmentRequest?->status === 'PENDING' ? Carbon::parse($this->latestReassignmentRequest?->created_at)->format('F d, Y') : null,
            'previously_assigned_to' => $this->latestReassignmentRequest?->status === 'APPROVED' && $this->latestReassignmentRequest?->operations
                ? mb_strtoupper($this->latestReassignmentRequest?->operations?->username) . ' ' . $this->latestReassignmentRequest?->operations?->last_name 
                : null,
        ];
    }
}
