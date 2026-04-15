<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOption;

class ServiceOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', ServiceOption::class);

        $serviceOptions = ServiceOption::whereNot('name', 'ALL IN')->get();
        return $this->success('Sub-services fetched successfully', $serviceOptions, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ServiceOption::class);

        $request->validate([
            'name' => 'required|string|unique:service_options,name',
        ]);

        $serviceOption = ServiceOption::create([
            'name' => $request->name,
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceOption $serviceOption)
    {
        $this->authorize('update', $serviceOption);

        $serviceOption = ServiceOption::findOrFail($serviceOption->id);

        $request->validate([
            'name' => 'sometimes|string|unique:service_options,name,' . $serviceOption->id,
            'status' => 'sometimes|in:ENABLED,DISABLED',
        ]);

        if ($serviceOption->name === 'ALL IN') {
            return $this->error('The ALL IN sub-service cannot be updated.', 403);
        }

        $serviceOption->update([
            'name' => $request->name ?? $serviceOption->name,
            'status' => $request->status ?? $serviceOption->status,
        ]);

        return $this->success('Sub-service updated successfully', $serviceOption, 200);
    }

    /**
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
