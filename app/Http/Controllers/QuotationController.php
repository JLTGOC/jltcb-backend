<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\ClientInputResource;
use App\Http\Resources\QuotationFileResource;
use App\Http\Resources\QuotationResource;
use App\Models\{
    LogisticsService,
    Quotation,
    User,
    ServiceOption,
    QuotationFile,
    Shipment,
    Message,
    QuotationTemplate,
    RegulatoryService,
    IssuedQuotation,
    BusinessType,
    RegulatoryAssistanceType,
    ContainerSize,
    ReassignmentRequest
};
use App\Repositories\Quotation\{
    IndexQuotationRepository,
    StoreQuotationRepository,
    ShowQuotationRepository,
    UpdateQuotationRepository,
    DestroyQuotationRepository,
    EnumQuotationOptionsRepository,
    UploadRepository,
    RequestReassignmentRepository,
    ReassignSpecialistRepository,
    AcceptQuotationProposalRepository,
    AcceptQuotationAssignmentRepository,
    ClientInputsRepository
};
use App\Services\QuotationFileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Searchable\Search;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class QuotationController extends Controller
{
    protected $quotationFileService;

    public function __construct(
        QuotationFileService $quotationFileService,
        IndexQuotationRepository $index,
        StoreQuotationRepository $store,
        ShowQuotationRepository $show,
        UpdateQuotationRepository $update,
        DestroyQuotationRepository $destroy,
        EnumQuotationOptionsRepository $enumOptions,
        UploadRepository $upload,
        RequestReassignmentRepository $requestReassignment,
        ReassignSpecialistRepository $reassignSpecialist,
        AcceptQuotationProposalRepository $acceptQuotationProposal,
        AcceptQuotationAssignmentRepository $acceptQuotationAssignment,
        ClientInputsRepository $clientInputs
    ) {
        $this->authorizeResource(Quotation::class, 'quotation');
        $this->middleware('can:enumQuotationOptions,' . Quotation::class)->only('enumQuotationOptions');
        $this->middleware('can:upload,quotation')->only('upload');
        $this->middleware('can:showFile,quotation')->only('showFile');
        $this->middleware('can:acceptQuotation,quotation')->only('acceptQuotation');

        $this->quotationFileService = $quotationFileService;

        $this->index = $index;
        $this->store = $store;
        $this->show = $show;
        $this->update = $update;
        $this->destroy = $destroy;
        $this->enumOptions = $enumOptions;
        $this->upload = $upload;
        $this->requestReassignment = $requestReassignment;
        $this->reassignSpecialist = $reassignSpecialist;
        $this->acceptQuotationProposal = $acceptQuotationProposal;
        $this->acceptQuotationAssignment = $acceptQuotationAssignment;
        $this->clientInputs = $clientInputs;
    }

    /**
     * Index Quotations
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        return $this->index->execute($request);
    }

    /**
     * Store Quotation
     * 
     * Request new quotation
     */
    public function store(StoreQuotationRequest $request)
    {
        return $this->store->execute($request);
    }

    /**
     * Show Quotation
     * 
     * Show individual quotation details
     */
    public function show(Quotation $quotation) {   
        return $this->show->execute($quotation);
    }

    /**
     * Update Quotation
     * 
     * Update quotation request details
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        return $this->update->execute($request, $quotation);
    }

    /**
     * Destroy Quotation
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation)
    {
        return $this->destroy->execute($quotation);
    }

    /**
     * Enum Quotation Options
     * 
     * Fetch enumeration options for quotations
     */
    public function enumQuotationOptions() {
        return $this->enumOptions->execute();
    }

    /**
     * Upload Quotation File
     * 
     * Uploads a file for the quotation
     */
    public function upload(Quotation $quotation, Request $request) {
        return $this->quotationFileService->upload($quotation, $request);
    } 

    /**
     * Reassign Account Specialist
     * 
     * Allows Lead Account Specialist to reassign the Account Specialist in charge of a quotation
     */
    public function reassignSpecialist(Quotation $quotation, Request $request) {
        $this->authorize('reassignSpecialist', $quotation);

        return $this->reassignSpecialist->execute($quotation, $request);
    }

    /**
     * Request Reassignment
     * 
     * Allows Account Specialist to request for reassignment of a quotation to another Account Specialist, changing the assignment status to REASSIGNMENT REQUESTED
     */
    public function requestReassignment(Quotation $quotation, Request $request) {
        $this->authorize('requestReassignment', $quotation);

        return $this->requestReassignment->execute($quotation, $request);
    }

    /**
     * Accept Quotation Assignment
     * 
     * Allows Account Specialist to accept a quotation assignment, changing the assignment status to ASSIGNED
     */
    public function acceptQuotationAssignment(Quotation $quotation, Request $request) {
        $this->authorize('acceptQuotationAssignment', $quotation);

        return $this->acceptQuotationAssignment->execute($quotation, $request);
    }

    /**
     * Accept Quotation Proposal
     * 
     * Allows Client to accept a quotation, changing its status to ACCEPTED
     */
    public function acceptQuotationProposal(Quotation $quotation, Request $request) {
        $this->authorize('acceptQuotationProposal', $quotation);

        return $this->acceptQuotationProposal->execute($quotation, $request);
    }

    /**
     * Show Client inputs
     * 
     * Show specific client quotation details based on quotation template configured
     */
    public function clientInputs(Quotation $quotation, Request $request) {
        return $this->clientInputs->execute($quotation, $request);
    }
}
