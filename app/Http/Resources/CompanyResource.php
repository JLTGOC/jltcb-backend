<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($request->routeIs('companies.index')) {
            return [
                'id' => 'C' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
                'name' => $this->name,
                'clasification' => $this->clientClassification ? $this->clientClassification->name : null,
                'consignee' => $this->consignee_used,
                'account_handler' => $this->accountHandler 
                    ? [
                        'id' => $this->accountHandler->id,
                        'full_name' => $this->accountHandler->full_name,
                        'username' => $this->accountHandler->username,
                        'role' => $this->accountHandler->roles()->first()->name ?? null,
                        'image_path' => $this->accountHandler->image_path,
                    ] : null,
            ];
        }

        elseif ($request->routeIs('companies.show')) {
            //
        }
    }
}
