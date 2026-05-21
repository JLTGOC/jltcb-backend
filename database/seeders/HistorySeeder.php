<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    ActivityLog,
    JobOrder,
    Quotation,
    QuotationFile,
    Shipment,
};
use Carbon\Carbon;

class HistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quotations = Quotation::with(["client", "accountSpecialist", "files", "jobOrder", "shipment"])->get();

        foreach ($quotations as $q) {
            $clientId = $q->client?->id;
            $quotationId = $q->id;

            if ($clientId) {
                $this->createQuotationHistory($clientId, $quotationId, 'Quotation Requested', $q->created_at ?? null);
            }

            $proposalFiles = $q->files->where('type', 'PROPOSAL');
            foreach ($proposalFiles as $file) {
                $asId = $q->accountSpecialist?->id;
                if ($asId) {
                    $this->createQuotationHistory($asId, $quotationId, 'Quotation Sent', $q->jobOrder ? Carbon::parse($q->jobOrder->created_at)->subDays(5) : $file->created_at);
                }
                if ($clientId) {
                    $this->createQuotationHistory($clientId, $quotationId, 'Quotation Seen By Client', $q->jobOrder ? Carbon::parse($q->jobOrder->created_at)->subDays(3) : Carbon::parse($file->created_at)->addMinutes(30));
                }
                if ($q->status === 'ACCEPTED' && $clientId) {
                    $this->createQuotationHistory($clientId, $quotationId, 'Quotation Accepted', $q->jobOrder ? Carbon::parse($q->jobOrder->created_at)->subDays(1) : Carbon::parse($file->created_at)->addHours(1));
                }
            }

            if ($q->jobOrder) {
                $jobOrder = $q->jobOrder;

                $asId = $jobOrder->as_id ?? null;
                if ($asId) {
                    $this->createJobOrderHistory($asId, $jobOrder->id, 'Job Order Created', $jobOrder->created_at ?? null);
                }
                $opsId = $jobOrder->operations_id ?? null;
                if ($opsId) {
                    $this->createJobOrderHistory($opsId, $jobOrder->id, 'Job Order Accepted', $jobOrder->assigned_at ?? null);
                }
            }

            if ($q->shipment) {
                $s = $q->shipment;
                $opsId = $s->operations_id ?? null;
                if ($opsId) {
                    $this->createShipmentHistory($opsId, $s->id, 'Shipment Created', $s->created_at ?? null);
                    $this->appendShipmentStatusEvents($s, $opsId);
                }
            }
        }
    }

    protected function createQuotationHistory($userId, $quotationId, $action, $createdAt = null): void
    {
        if (! $userId) {
            return;
        }

        $data = [
            'user_id' => $userId,
            'subject_id' => $quotationId,
            'subject_type' => Quotation::class,
            'action' => $action,
        ];

        if ($createdAt) {
            $data['created_at'] = Carbon::parse($createdAt);
        }

        ActivityLog::create($data);
    }

    protected function createShipmentHistory($userId, $shipmentId, $action, $createdAt = null): void
    {
        if (! $userId) {
            return;
        }

        $data = [
            'user_id' => $userId,
            'subject_id' => $shipmentId,
            'subject_type' => Shipment::class,
            'action' => $action,
        ];

        if ($createdAt) {
            $data['created_at'] = Carbon::parse($createdAt);
        }

        ActivityLog::create($data);
    }

    protected function createJobOrderHistory($userId, $jobOrderId, $action, $createdAt = null): void
    {
        if (! $userId) {
            return;
        }

        $data = [
            'user_id' => $userId,
            'subject_id' => $jobOrderId,
            'subject_type' => JobOrder::class,
            'action' => $action,
        ];

        if ($createdAt) {
            $data['created_at'] = Carbon::parse($createdAt);
        }

        ActivityLog::create($data);
    }

    protected function appendShipmentStatusEvents($shipment, $userId): void
    {
        $status = $shipment->status;
        $updated = $shipment->updated_at;

        $map = [
            'IN TRANSIT' => [
                ['Shipment In Transit', 0],
            ],
            'ARRIVED' => [
                ['Shipment In Transit', -1],
                ['Shipment Arrived', 0],
            ],
            'BERTHED' => [
                ['Shipment In Transit', -2],
                ['Shipment Arrived', -1],
                ['Shipment Berthed', 0],
            ],
            'DISCHARGED' => [
                ['Shipment In Transit', -3],
                ['Shipment Arrived', -2],
                ['Shipment Berthed', -1],
                ['Shipment Discharged', 0],
            ],
            'DELIVERED' => [
                ['Shipment In Transit', -4],
                ['Shipment Arrived', -3],
                ['Shipment Berthed', -2],
                ['Shipment Discharged', -1],
                ['Shipment Delivered', 0],
            ],
        ];

        if (! isset($map[$status]) || ! $updated) {
            return;
        }

        foreach ($map[$status] as [$action, $offsetDays]) {
            $date = Carbon::parse($updated)->addDays($offsetDays);
            $this->createShipmentHistory($userId, $shipment->id, $action, $date);
        }
    }
}
