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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Index Companies
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $asSearch = $request->input('as_search');

        $companiesQuery = Company::query();

        if ($asSearch) {
            $companies = $companiesQuery->where(function ($query) use ($asSearch) {
                $query->whereHas('accountHandler', function ($q) use ($asSearch) {
                    $q->where('username', 'like', "%{$asSearch}%")
                    ->orWhere('full_name', 'like', "%{$asSearch}%");
                });
            });
        }
        return $this->success('All companies fetched successfully', CompanyResource::collection($companiesQuery->get()), 200);
    }

    /**
     * Store Company
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
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

            return $this->success('Company created successfully.', new CompanyResource($company), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create company', 500, $e->getMessage());
        }
    }

    /**
     * Show Company
     * 
     * Display the specified resource.
     */
    public function show(Company $company, Request $request)
    {
        $fields = [
            'basic_info',
            'address',
            'contacts',
            'registration',
            'pricing',
            'operation',
            'monitoring',
            'documents',
            'insights',
        ];

        $booleanValueRule = function ($attribute, $value, $fail) {
            if (is_bool($value) || is_int($value)) {
                return true;
            }

            if (is_string($value)) {
                $normalizedValue = strtolower($value);

                if (in_array($normalizedValue, ['true', 'false', '1', '0'], true)) {
                    return true;
                }
            }

            $fail("The {$attribute} field must be true or false.");
        };

        $validator = Validator::make($request->all(), [
            'basic_info' => ['sometimes', $booleanValueRule],
            'address' => ['sometimes', $booleanValueRule],
            'contacts' => ['sometimes', $booleanValueRule],
            'registration' => ['sometimes', $booleanValueRule],
            'pricing' => ['sometimes', $booleanValueRule],
            'operation' => ['sometimes', $booleanValueRule],
            'monitoring' => ['sometimes', $booleanValueRule],
            'documents' => ['sometimes', $booleanValueRule],
            'insights' => ['sometimes', $booleanValueRule],
        ]);

        $validator->after(function ($validator) use ($request, $fields) {
            $trueFields = collect($fields)->filter(function ($field) use ($request) {
                return $request->boolean($field);
            });

            if ($trueFields->count() > 1) {
                $validator->errors()->add('show', 'Only one company section can be true at a time.');
            } elseif ($trueFields->isEmpty()) {
                $request->basic_info = true; // Default to basic_info if no section is specified
            }
        });

        $validator->validate();

        return $this->success('Company details fetched successfully.', new CompanyResource($company), 200);
    }

    /**
     * Update Company
     * 
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        DB::beginTransaction();

        try{
            $basicInfo = $request->input('basic_info');
            if ($basicInfo) {
                $company->update([
                    'name' => $basicInfo['name'] ?? $company->name,
                    'consignee_used' => $basicInfo['consignee_used'] ?? $company->consignee_used,
                    'trade_name' => $basicInfo['trade_name'] ?? $company->trade_name,
                    'account_handler_id' => $basicInfo['account_handler_id'] ?? $company->account_handler_id,
                    'transaction_type_id' => $basicInfo['transaction_type_id'] ?? $company->transaction_type_id,
                    'client_classification_id' => $basicInfo['client_classification_id'] ?? $company->client_classification_id,
                    'company_type_id' => $basicInfo['company_type_id'] ?? $company->company_type_id,
                    'business_type_id' => $basicInfo['business_type_id'] ?? $company->business_type_id,
                    'business_registration_number' => $basicInfo['business_registration_number'] ?? $company->business_registration_number,
                    'website' => $basicInfo['website'] ?? $company->website,
                    'years_in_operation' => $basicInfo['years_in_operation'] ?? $company->years_in_operation,
                    'activation_date' => isset($basicInfo['activation_date']) ? Carbon::parse($basicInfo['activation_date'])->format('Y-m-d') : $company->activation_date,
                ]);
            }
            if (isset($basicInfo['industry'])) {
                $company->companyIndustries()->delete();
                $company->companyIndustries()->createMany(array_map(function ($industryId) {
                    return ['industry_id' => $industryId];
                }, $basicInfo['industry']));
            }

            $address = $request->input('address');
            if ($address) {
                $company->address()->update([
                    'registered_address' => $address['registered_address'] ?? $company->address->registered_address,
                    'office_address' => $address['office_address'] ?? $company->address->office_address,
                    'usual_port' => $address['usual_port'] ?? $company->address->usual_port,
                    'origin_country' => $address['origin_country'] ?? $company->address->origin_country,
                    'destination_country' => $address['destination_country'] ?? $company->address->destination_country,
                ]);

                if ($address['warehouse_addresses']) {
                    $company->warehouseAddresses()->delete();
                    foreach ($address['warehouse_addresses'] as $warehouseAddress) {
                        $company->warehouseAddresses()->create(['address' => $warehouseAddress]);
                    }
                }
                if ($address['delivery_addresses']) {
                    $company->deliveryAddresses()->delete();
                    foreach ($address['delivery_addresses'] as $deliveryAddress) {
                        $company->deliveryAddresses()->create(['address' => $deliveryAddress]);
                    }
                }
            }

            $primaryContact = $request->input('primary');
            $companyPrimaryContact = $company->contacts()->where('type', 'PRIMARY')->first();
            $secondaryContact = $request->input('secondary');
            $companySecondaryContact = $company->contacts()->where('type', 'SECONDARY')->first();
            $billingContact = $request->input('billing');
            $companyBillingContact = $company->contacts()->where('type', 'BILLING')->first();
            if ($primaryContact && $companyPrimaryContact) {
                $company->contacts()->where('type', 'PRIMARY')->update([
                    'full_name' => $primaryContact['full_name'] ?? $companyPrimaryContact->full_name,
                    'position' => $primaryContact['position'] ?? $companyPrimaryContact->position,
                    'email' => $primaryContact['email'] ?? $companyPrimaryContact->email,
                    'contact_number' => $primaryContact['contact_number'] ?? $companyPrimaryContact->contact_number,
                ]);
            }
            if ($secondaryContact && $companySecondaryContact) {
                $company->contacts()->where('type', 'SECONDARY')->update([
                    'full_name' => $secondaryContact['full_name'] ?? $companySecondaryContact->full_name,
                    'position' => $secondaryContact['position'] ?? $companySecondaryContact->position,
                    'email' => $secondaryContact['email'] ?? $companySecondaryContact->email,
                    'contact_number' => $secondaryContact['contact_number'] ?? $companySecondaryContact->contact_number,
                ]);
            }
            if ($billingContact && $companyBillingContact) {
                $company->contacts()->where('type', 'BILLING')->update([
                    'full_name' => $billingContact['full_name'] ?? $companyBillingContact->full_name,
                    'position' => $billingContact['position'] ?? $companyBillingContact->position,
                    'email' => $billingContact['email'] ?? $companyBillingContact->email,
                    'contact_number' => $billingContact['contact_number'] ?? $companyBillingContact->contact_number,
                ]);
            }

            $registration = $request->input('registration');
            if ($registration) {
                $company->registration()->update([
                    'tin' => $registration['tin'] ?? $company->registration->tin,
                    'bir_registration_number' => $registration['bir_registration_number'] ?? $company->registration->bir_registration_number,
                    'cprs_status' => $registration['cprs_status'] ?? $company->registration->cprs_status,
                    'importer_accreditation_number' => $registration['importer_accreditation_number'] ?? $company->registration->importer_accreditation_number,
                    'importer_accreditation_expiry' => isset($registration['importer_accreditation_expiry']) ? Carbon::parse($registration['importer_accreditation_expiry'])->format('Y-m-d') : $company->registration->importer_accreditation_expiry,
                ]);

                if ($registration['representatives']) {
                    $company->representatives()->delete();
                    foreach ($registration['representatives'] as $representative) {
                        $company->representatives()->create(['full_name' => $representative]);
                    }
                }
            }

            $pricing = $request->input('pricing');
            if ($pricing) {
                $company->pricing()->update($pricing);
            }

            $monitoring = $request->input('monitoring');
            if ($monitoring) {
                $company->monitoring()->update($monitoring);
            }

            $operation = $request->input('operation');
            if ($operation) {
                $company->operation()->update($operation);
            }

            $insights = $request->input('insights');
            if ($insights) {
                $company->insight()->update($insights);
            }

            if ($request->has('documents_to_delete')) {
                $company->documents()->whereIn('id', $request->input('documents_to_delete'))->delete();
            }

            $documentsInput = $request->input('documents', []);
            $uploadedDocs = $request->file('documents', []);
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
                if (isset($doc['id'])) {
                    $company->documents()->where('id', $doc['id'])->update([
                        'file_name' => $fileName,
                        'file_type' => $fileType,
                        'filepath' => $storedPath,
                    ]);
                } else {
                    $company->documents()->create([
                        'file_name' => $fileName,
                        'file_type' => $fileType,
                        'filepath' => $storedPath,
                    ]);
                }
            }

            DB::commit();
            return $this->success('Company updated successfully.', new CompanyResource($company), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update company', 500, $e->getMessage());
        }
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
        $accountHandlers = User::role(['Account Specialist', 'Lead Account Specialist', 'Client Success', 'Lead Client Success'])->get(['id', 'username', 'full_name']);
        $transactionTypes = TransactionType::all();
        $clientClassifications = ClientClassification::all();
        $companyTypes = CompanyType::all();
        $industries = Industry::all();
        $businessTypes = BusinessType::all();
        $growth = ['LOW', 'MEDIUM', 'HIGH'];

        return response()->json([
            'account_handlers' => $accountHandlers->map(function ($handler) {
                return [
                    'id' => $handler->id,
                    'username' => mb_strtoupper($handler->username) . ' ' . mb_strtoupper($handler->full_name),
                ];
            }),
            'transaction_types' => $transactionTypes,
            'client_classifications' => $clientClassifications,
            'company_types' => $companyTypes,
            'industries' => $industries,
            'business_types' => $businessTypes,
            'growth_levels' => $growth,
        ]);
    }
}
