<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\{
    Shipment,
    Quotation,
    JobOrder
};

class ClientAccountsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $platform = strtolower((string) $request->header('Platform', 'mobile'));
        $isWeb = $platform === 'web';

        if ($isWeb) {
            $clientQuotations = Quotation::where('client_id', $this->id);
            return [
                'id' => $this->id,
                'full_name' => $this->full_name,
                'company_name' => $this->company_name,
                'email' => $this->email,
                'contact_number' => $this->contact_number,
                'type' => $clientQuotations->count() > 1 ? 'OLD' : 'NEW',
                'pending_quotations' => $clientQuotations->whereNotIn('status', ['ACCEPTED'])->count(),
                'active_shipments' => Shipment::where('client_id', $this->id)->whereNotIn('status', ['DELIVERED'])->count(),
                'active_regulatory' => JobOrder::where('client_id', $this->id)->where('job_type', 'REGULATORY')->count(),
            ];
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'ongoing_shipments' => Shipment::where('client_id', $this->id)->whereIn('status', ['NOT YET DELIVERED', 'IN TRANSIT', 'ARRIVED', 'BERTHED', 'DISCHARGED'])->count(),
            'completed_shipments' => Shipment::where('client_id', $this->id)->where('status', 'DELIVERED')->count(),
        ];
    }
}
