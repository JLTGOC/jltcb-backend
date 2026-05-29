<?php

namespace App\Http\Controllers;

use App\Models\{
    Company,
    User,
    TransactionType,
    ClientClassification,
    CompanyType,
    Industry,
    BusinessType,
};
use App\Http\Resources\{
    CompanyResource
};
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
     * Index Companies
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store Company
     * 
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
                    $company->representatives()->create(['full_name' => $representative]);
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
                $uploaded = data_get($uploadedDocs, "{$index}.file");
                $fileName = $doc['name'] ?? null;

                if ($uploaded instanceof UploadedFile) {
                    $storedPath = $uploaded->store('files', 'local');
                    $fileType = $uploaded->getClientOriginalExtension();
                } else {
                    $storedPath = isset($doc['filepath']) ? $doc['filepath'] : null;
                    $fileType = $doc['file_type'] ?? null;
                }

                $documentsToCreate[] = [
                    'filepath' => $storedPath,
                    'file_name' => $fileName,
                    'file_type' => $fileType,
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
     * Show Company
     * 
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Update Company
     * 
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        //
    }

    /**
     * Delete Company
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }

    /**
     * Company Enums
     * 
     * Get enums for company creation and updates
     */
    public function enums()
    {
        $transactionTypes = TransactionType::all();
        $clientClassifications = ClientClassification::all();
        $companyTypes = CompanyType::all();
        $industries = Industry::all();
        $businessTypes = BusinessType::all();
        $growth = ['LOW', 'MEDIUM', 'HIGH'];

        return response()->json([
            'transaction_types' => $transactionTypes,
            'client_classifications' => $clientClassifications,
            'company_types' => $companyTypes,
            'industries' => $industries,
            'business_types' => $businessTypes,
            'growth_levels' => $growth,
        ]);
    }
}
