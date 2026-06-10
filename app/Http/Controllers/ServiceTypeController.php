<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceType;

class ServiceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ServiceType::class);

        $request->validate([
            'service_category' => 'required|in:LOGISTICS,REGULATORY',
        ]);

        $serviceTypes = ServiceType::where('service', $request->service_category)->orderBy('id', 'asc')->get(['id', 'name', 'code']);

        return $this->success('Service types fetched successfully', $serviceTypes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:service_types,code',
            'service' => 'required|in:LOGISTICS,REGULATORY',
        ]);

        $serviceType = ServiceType::create([
            'name' => $request->name,
            'code' => $request->code,
            'service' => $request->service,
            'status' => 'ENABLED',
        ]);

        return $this->success('Service type created successfully', $serviceType, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceType $serviceType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceType $serviceType)
    {
        $request->validate([
            'name' => 'sometimes|nullable|string',
            'code' => 'sometimes|nullable|string|unique:service_types,code,' . $serviceType->id,
            'service' => 'sometimes|in:LOGISTICS,REGULATORY',
            'status' => 'sometimes|in:ENABLED,DISABLED',
        ]);

        $serviceType->update([
            'name' => $request->name ?? $serviceType->name,
            'code' => $request->code ?? $serviceType->code,
            'service' => $request->service ?? $serviceType->service,
            'status' => $request->status ?? $serviceType->status,
        ]);

        return $this->success('Service type updated successfully', $serviceType, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceType $serviceType)
    {
        if ($serviceType->isLocked()) {
            return $this->error('Cannot delete service type because it has associated records.', 422);
        }
        
        $serviceType->delete();

        return $this->success('Service type deleted successfully', null, 200);
    }
}
