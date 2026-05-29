<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseConfigController;
use App\Http\Resources\DetailsConfigResource;
use App\Models\QuotationTemplateConfig\{
    DetailsConfiguration,
    ConfigDropdownOption
};
use Closure;
use Illuminate\Http\Request;

class DetailsConfigController extends BaseConfigController
{
    protected function model(): string
    {
        return DetailsConfiguration::class;
    }

    protected function resource(): string
    {
        return DetailsConfigResource::class;
    }

    protected function allowedTypes(): array
    {
        return ['DROPDOWN', 'TEXT', 'DATE PICKER'];
    }

    public function __construct()
    {
        $this->authorizeResource(DetailsConfiguration::class, 'record', [
            'except' => ['show', 'update', 'destroy'],
        ]);
    }

    /**
     * Index Details Configurations
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
        $request->validate([
            'label' => 'required|string|unique:details_configurations,label|max:255',
            'type' => 'required|in:DROPDOWN,TEXT,DATE PICKER',
            'options' => 'required_if:type,DROPDOWN|array',
            'options.*.name' => 'required|string|max:255|distinct',
        ]);
        
        // return $request->options;

        $detailsConfig = DetailsConfiguration::create([
            'label' => $request->label,
            'type' => $request->type
        ]);

        if ($detailsConfig->type === 'DROPDOWN') {
            $detailsConfig->dropdownOptions()->createMany($request->options);
            $detailsConfig->load('dropdownOptions');
        }

        return $this->success('Details Configuration stored sucessfully', new DetailsConfigResource($detailsConfig));
    }

    /**
     * Show Details Configuration
     * 
     * Display the specified resource.
     */
    public function show($record)
    {
        $record = DetailsConfiguration::findOrFail($record);
        $this->authorize('view', $record);

        if ($record->type === 'DROPDOWN') {
            $record->load('dropdownOptions');
        }
        
        return parent::show($record);
    }

    /**
     * Update Details Configuration
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, $record)
    {
        $detailsConfig = DetailsConfiguration::findOrFail($record);
        $this->authorize('update', $detailsConfig);

        $request->validate([
            'label' => 'required|string|max:255|unique:details_configurations,label,' . $record,
            'options' => 'array',
            'options.*.name' => 'required|string|max:255|distinct',
            'options.*.id' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail) use ($detailsConfig) {
                    $existingOptionIds = $detailsConfig->dropdownOptions()->pluck('id')->toArray();
                    if (!in_array($value, $existingOptionIds)) {
                        $fail('This id does not exist or does not belong to this dropdown options');
                    }
                },
            ]
        ]);

        $detailsConfig->update([
            'label' => $request->label,
        ]);

        if ($detailsConfig->type === 'DROPDOWN') {

            $existingIds = $detailsConfig->dropdownOptions()->pluck('id')->toArray();

            $incomingIds = collect($request->options)
                ->pluck('id')
                ->filter()
                ->toArray();

            $toDelete = array_diff($existingIds, $incomingIds);
            if (!empty($toDelete)) {
                ConfigDropdownOption::whereIn('id', $toDelete)->delete();
            }

            foreach ($request->options as $option) {
                if (isset($option['id'])) {
                    ConfigDropdownOption::where('id', $option['id'])
                        ->update(['name' => $option['name']]);
                } else {
                    $detailsConfig->dropdownOptions()->create([
                        'name' => $option['name']
                    ]);
                }
            }

            $detailsConfig->load('dropdownOptions');
        }

        return $this->success(
            'Updated successfully',
            new DetailsConfigResource($detailsConfig)
        );
    }

    /**
     * Delete Details Configuration
     * 
     * Remove the specified resource from storage.
     */
    public function destroy($record)
    {
        $record = DetailsConfiguration::findOrFail($record);
        $this->authorize('delete', $record);

        return parent::destroy($record);
    }
}