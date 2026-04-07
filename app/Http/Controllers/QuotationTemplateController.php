<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationTemplateRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Requests\UpdateQuotationTemplateRequest;
use App\Http\Resources\BillingConfigResource;
use App\Http\Resources\DetailsConfigResource;
use App\Http\Resources\QuotationTemplateResource;
use App\Models\QuotationTemplate;
use App\Models\TemplateCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationTemplateController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(QuotationTemplate::class, 'template', [
            'except' => ['show', 'update', 'destroy']
        ]);
    }

    /**
     * Index Templates
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'sometimes|in:EXPORT,IMPORT,BUSINESS SOLUTION'
        ]);

        $templateQuery = QuotationTemplate::query();

        $templateType = $request->type;
        if ($templateQuery) {
            if (in_array($templateType, ['IMPORT', 'EXPORT'])) {
                $templateQuery->where('service_type', 'LOGISTICS');
            } elseif($templateType === 'BUSINESS SOLUTION') {
                $templateQuery->where('service_type', 'REGULATORY');
            }
        }

        return $this->success(
            'Quotation templates fetched successfully', 
            QuotationTemplateResource::collection($templateQuery->get())
        );
    }

    /**
     * Store Templates
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreQuotationTemplateRequest $request)
    {
        $template = DB::transaction(function() use ($request) {
            $template = QuotationTemplate::create([
            'name' => $request->name,
            'service_type' => $request->service_type,
            ]);

            $template->detailConfigs()->sync($request->detail_config_ids);

            $template->quotationFields()->sync($request->quotation_field_ids);

            $chargeInputs = $request->template_charges;

            foreach($chargeInputs as $chargeInput) {
                $templateCharge = $template->templateCharges()->create([
                    'name' => $chargeInput['name']
                ]);

                $templateCharge->allowedReceiptCharges()->sync($chargeInput['receipt_option_ids']);
            }

            return $template;
        });

        $template->load([
            'detailConfigs.dropdownOptions', 
            'templateCharges.allowedReceiptCharges',
        ]);

        return $this->success(
            'Quotation Template stored successfully', 
            new QuotationTemplateResource($template),
            201
        );
    }

    /**
     * Show Templates
     * 
     * Display the specified resource.
     */
    public function show(QuotationTemplate $template)
    {
        $this->authorize('view', $template);

        $template->load([
            'detailConfigs.dropdownOptions',
            'templateCharges.allowedReceiptCharges', 
        ]);

        return $this->success('Quotation template fetched successfully', new QuotationTemplateResource($template));
    }

    /**
     * Update Templates
     * 
     * Update the specified resource in storage.
     */
    public function update(UpdateQuotationTemplateRequest $request, QuotationTemplate $template)
    {
        $this->authorize('update', $template);

        DB::transaction(function() use ($request, $template) {
            $template->update([
                'name' => $request->name,
            ]);

            $template->detailConfigs()->sync($request->detail_config_ids);

            $template->quotationFields()->sync($request->quotation_field_ids);

            $chargeInputs = $request->template_charges;

            $incomingChargeIds = collect($chargeInputs)->pluck('id')->filter()->toArray();
            $existingChargeIds = $template->templateCharges()->pluck('id')->toArray();
            $toDelete = array_diff($existingChargeIds, $incomingChargeIds);

            if (!empty($toDelete)) {
                TemplateCharge::whereIn('id', $toDelete)->delete();
            }

            foreach($chargeInputs as $chargeInput) {
                if (!empty($chargeInput['id'])) {
                    $templateCharge = $template->templateCharges()->findOrFail($chargeInput['id']);
                    $templateCharge->update([
                        'name' => $chargeInput['name']
                    ]);
                } else {
                    $templateCharge = $template->templateCharges()->create([
                        'name' => $chargeInput['name']
                    ]);
                }
                
                $templateCharge->allowedReceiptCharges()->sync($chargeInput['receipt_option_ids']);
            }
        });

        $template->load([
            'detailConfigs.dropdownOptions', 'templateCharges.allowedReceiptCharges',
        ]);

        return $this->success(
            'Quotation Template Updated Successfully', 
            new QuotationTemplateResource($template)
        );
    }

    /**
     * Delete Templates
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(QuotationTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return $this->success('Quotation Template deleted successfully');
    }
}
