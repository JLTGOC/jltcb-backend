<?php

namespace App\Http\Controllers;

use App\Models\{
    BusinessType,
    ClientClassification,
    CompanyType,
    TransactionType,
    Industry,
    CompanyIndustry,
    Company,
    CompanyAddress,
    CompanyContact,
    CompanyRegistration,
    CompanyPricing,
    CompanyMonitoring,
    CompanyOperation,
    CompanyDocument,
    CompanyInsight,
    CompanyWarehouseAddress,
    CompanyDeliveryAddress,
    CompanyRepresentative,
    User
};
use App\Http\Resources\{
    CompanyResource
};
use Illuminate\Http\Request;
use App\Http\Requests\{
    StoreCompanyRequest,
    UpdateCompanyRequest
};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        $request->validated();

        DB::beginTransaction();

        try {
            $company = Company::create($request->input('basic_info'));
            $company->companyIndustries()->createMany(array_map(function ($industryId) {
                return ['industry_id' => $industryId];
            }, $request->input('basic_info.industry', [])));

            $company->address()->create($request->input('address'));
            if ($request->has('address.warehouse_addresses')) {
                foreach ($request->input('address.warehouse_addresses') as $warehouseAddress) {
                    $company->warehouseAddresses()->create(['address' => $warehouseAddress]);
                }
            }
            if ($request->has('address.delivery_addresses')) {
                foreach ($request->input('address.delivery_addresses') as $deliveryAddress) {
                    $company->deliveryAddresses()->create(['address' => $deliveryAddress]);
                }
            }

            $company->contacts()->create($request->input('primary'));
            $company->contacts()->create($request->input('secondary'));
            $company->contacts()->create($request->input('billing'));

            $company->registration()->create($request->input('registration'));
            if ($request->has('registration.representatives')) {
                foreach ($request->input('registration.representatives') as $representative) {
                    $company->representatives()->create(['name' => $representative]);
                }
            }

            $company->pricing()->create($request->input('pricing'));
            $company->monitoring()->create($request->input('monitoring'));
            $company->operation()->create($request->input('operation'));
            $company->insight()->create($request->input('insights'));

            $documentsInput = $request->input('documents', []);
            $uploadedDocs = $request->file('documents', []);
            $documentsToCreate = [];

            foreach ($documentsInput as $index => $doc) {
                $uploaded = $uploadedDocs[$index] ?? null;
                $fileName = $doc['name'] ?? ($uploaded ? $uploaded->getClientOriginalName() : null);

                if ($uploaded && $fileName) {
                    $storedPath = $uploaded->storeAs("files/{$company->name}", $fileName);
                } else {
                    $storedPath = isset($doc['filepath']) ? $doc['filepath'] : null;
                }

                $documentsToCreate[] = [
                    'filepath' => $storedPath,
                    'file_name' => $fileName,
                    'file_type' => $fileName ? originalClientExtension($fileName) : ($uploaded ? $uploaded->getClientOriginalExtension() : null),
                ];
            }

            if (!empty($documentsToCreate)) {
                $company->documents()->createMany($documentsToCreate);
            }

            DB::commit();

            return new CompanyResource($company);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create company', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }
}
