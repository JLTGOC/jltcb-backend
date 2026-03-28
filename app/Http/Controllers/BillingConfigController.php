<?php

namespace App\Http\Controllers;

use App\Http\Resources\BillingConfigResource;
use App\Models\BillingConfiguration;
use Illuminate\Http\Request;

class BillingConfigController extends BaseConfigController
{
    protected function model(): string
    {
        return BillingConfiguration::class;
    }

    protected function resource(): string
    {
        return BillingConfigResource::class;
    }

    protected function allowedTypes(): array
    {
        return ['RECEIPT CHARGES', 'UOM', 'CURRENCY'];
    }

    public function __construct()
    {
        $this->authorizeResource(BillingConfiguration::class, 'record', [
            'except' => ['show', 'update', 'destroy'],
        ]);
    }

    /**
     * Index Billing Configurations
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return parent::store($request);
    }

    /**
     * Show Billing Configuration
     * 
     * Display the specified resource.
     */
    public function show($record)
    {
        $record = BillingConfiguration::findOrFail($record);
        $this->authorize('view', $record);

        return parent::show($record);
    }

    /**
     * Update Billing Configuration
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, $record)
    {
        $record = BillingConfiguration::findOrFail($record);
        $this->authorize('update', $record);

        return parent::update($request, $record);
    }

    /**
     * Delete Billing Configuration
     * 
     * Remove the specified resource from storage.
     */
    public function destroy($record)
    {
        $record = BillingConfiguration::findOrFail($record);
        $this->authorize('delete', $record);

        return parent::destroy($record);
    }
}