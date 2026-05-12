<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'role' => $this->getRoleNames()->first(),
            'username' => $this->username,
            'email' => $this->email,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'company_name' => $this->company_name,
            'company_address' => $this->company_address,
            'company_position' => $this->company_position,
            'business_type' => $this->business_type,
            'image_path' => $this->image_path ? asset(Storage::url($this->image_path)) : null,
            'id_image_path' => $this->id_image_path ? asset(Storage::url($this->id_image_path)) : null,
            'tabs' => [
                'dashboard' => $this->can('dashboard.view'),
                'leads' => $this->can('leads.view'),
                'quotations' => $this->can('quotations.view'),
                'shipments' =>$this->can('shipments.view'),
                'accounts' => $this->can('accounts.view'),
                'job_orders' => $this->can('job_orders.view'),
                'templates' => $this->can('templates.view')
            ],
            'permissions' => $this->getAllPermissions()
                ->pluck('name')
                ->reject(fn($p) => str_ends_with($p, '.view'))
                ->values(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
