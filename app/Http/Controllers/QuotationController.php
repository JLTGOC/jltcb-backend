<?php

namespace App\Http\Controllers;

use App\Http\Requests\{
    IndexQuotationRequest,
    StoreQuotationRequest,
    UpdateQuotationRequest,
    RequestQuotationReassignmentRequest,
    ReassignQuotationSpecialistRequest,
    QuotationClientInputsRequest,
    QuotationUploadRequest,
    EnumQuotationOptionsRequest,
};
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
    public function index(IndexQuotationRequest $request) {
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
    public function enumQuotationOptions(EnumQuotationOptionsRequest $request) {
        return $this->enumOptions->execute($request);
    }

    /**
     * Upload Quotation File
     * 
     * Uploads a file for the quotation
     */
    public function upload(Quotation $quotation, QuotationUploadRequest $request) {
        return $this->upload->execute($quotation, $request);
    }

    /**
     * Reassign Account Specialist
     * 
     * Allows Lead Account Specialist to reassign the Account Specialist in charge of a quotation
     */
    public function reassignSpecialist(Quotation $quotation, ReassignQuotationSpecialistRequest $request) {
        $this->authorize('reassignSpecialist', $quotation);

        return $this->reassignSpecialist->execute($quotation, $request);
    }

    /**
     * Request Reassignment
     * 
     * Allows Account Specialist to request for reassignment of a quotation to another Account Specialist, changing the assignment status to REASSIGNMENT REQUESTED
     */
    public function requestReassignment(Quotation $quotation, RequestQuotationReassignmentRequest $request) {
        $this->authorize('requestReassignment', $quotation);

        return $this->requestReassignment->execute($quotation, $request);
    }

    /**
     * Accept Quotation Assignment
     * 
     * Allows Account Specialist to accept a quotation assignment, changing the assignment status to ASSIGNED
     */
    public function acceptQuotationAssignment(Quotation $quotation) {
        $this->authorize('acceptQuotationAssignment', $quotation);

        return $this->acceptQuotationAssignment->execute($quotation);
    }

    /**
     * Accept Quotation Proposal
     * 
     * Allows Client to accept a quotation, changing its status to ACCEPTED
     */
    public function acceptQuotationProposal(Quotation $quotation) {
        $this->authorize('acceptQuotationProposal', $quotation);

        return $this->acceptQuotationProposal->execute($quotation);
    }

    /**
     * Show Client inputs
     * 
     * Show specific client quotation details based on quotation template configured
     */
    public function clientInputs(Quotation $quotation, QuotationClientInputsRequest $request) {
        return $this->clientInputs->execute($quotation, $request);
    }
}
