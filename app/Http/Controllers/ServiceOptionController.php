<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOption;
use App\Models\ServiceType;

class ServiceOptionController extends Controller
{
    /**
     * Index Sub Services
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ServiceOption::class);

        $request->validate([
            'service_type' => 'required|in:IMPORT,EXPORT,BUSINESS SOLUTION',
        ]);

        $serviceOptions = ServiceOption::whereNot('name', 'ALL IN')
            ->where('service_type_id', ServiceType::where('name', $request->service_type)->first()->id)
            ->orderBy('id', 'asc')
            ->get();

        $serviceOptions = $serviceOptions->map(function ($option) {
            return [
                'id' => $option->id,
                'name' => $option->name,
                'status' => $option->status,
                'service_type' => $option->serviceType->name,
            ];
        });
        
        return $this->success('Sub-services fetched successfully', $serviceOptions, 200);
    }

    /**
     * Store Sub Service
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ServiceOption::class);

        $request->validate([
            'name' => 'required|string',
            'service_type' => 'required|in:IMPORT,EXPORT,BUSINESS SOLUTION',
        ]);

        if (ServiceOption::where('name', $request->name)->where('service_type_id', ServiceType::where('name', $request->service_type)->first()->id)->exists()) {
            return $this->error('A sub-service with the same name already exists for the specified service type.', 422);
        }

        $serviceOption = ServiceOption::create([
            'name' => $request->name,
            'service_type_id' => ServiceType::where('name', $request->service_type)->first()->id,
            'status' => 'ENABLED',
        ]);

        return $this->success('Sub-service created successfully', $serviceOption, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update Sub Service
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceOption $serviceOption)
    {
        $this->authorize('update', $serviceOption);

        $serviceOption = ServiceOption::findOrFail($serviceOption->id);

        $request->validate([
            'name' => 'sometimes|string',
            'status' => 'sometimes|in:ENABLED,DISABLED',
        ]);

        if ($serviceOption->name === 'ALL IN') {
            return $this->error('The ALL IN sub-service cannot be updated.', 403);
        }

        if (isset($request->name) && ServiceOption::where('name', $request->name)->where('service_type_id', $serviceOption->service_type_id)->where('id', '!=', $serviceOption->id)->exists()) {
            return $this->error('A sub-service with the same name already exists for the specified service type.', 422);
        }

        $serviceOption->update([
            'name' => $request->name ?? $serviceOption->name,
            'status' => $request->status ?? $serviceOption->status,
        ]);

        return $this->success('Sub-service updated successfully', $serviceOption, 200);
    }

    /**
     * Delete Sub Service
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceOption $serviceOption)
    {
        $this->authorize('delete', $serviceOption);

        $option = ServiceOption::findOrFail($serviceOption->id);

        if ($option->name === 'ALL IN') {
            return $this->error('The ALL IN sub-service cannot be deleted.', 403);
        }

        $option->delete();

        return $this->success('Sub-service deleted successfully', null, 200);
    }
}
