<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\JobOrder\{
    IndexJobOrderRequest,
    StoreJobOrderRequest,
    JobOrderEnumsRequest,
    RequestReassignmentRequest,
    ReassignOpsRequest,
};
use App\Repositories\JobOrder\{
    IndexJobOrderRepository,
    StoreJobOrderRepository,
    ShowJobOrderRepository,
    JobOrderEnumsRepository,
    ShowJobOrderQuotationRepository,
    AcceptJobOrderRepository,
    RequestReassignmentRepository,
    ReassignOpsRepository,
};
use App\Models\JobOrder;

class JobOrderController extends Controller
{
    public function __construct(
        IndexJobOrderRepository $index,
        StoreJobOrderRepository $store,
        ShowJobOrderRepository $show,
        JobOrderEnumsRepository $enums,
        ShowJobOrderQuotationRepository $showQuotation,
        AcceptJobOrderRepository $accept,
        RequestReassignmentRepository $requestReassignment,
        ReassignOpsRepository $reassignOps,
    )
    {
        $this->authorizeResource(JobOrder::class, 'job_order');

        $this->index = $index;
        $this->store = $store;
        $this->show = $show;
        $this->enums = $enums;
        $this->showQuotation = $showQuotation;
        $this->accept = $accept;
        $this->requestReassignment = $requestReassignment;
        $this->reassignOps = $reassignOps;
    }

    /**
     * Index Job Orders
     * 
     * Display a listing of the resource.
     */
    public function index(IndexJobOrderRequest $request)
    {
        return $this->index->execute($request);
    }

    /**
     * Store Job Order
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreJobOrderRequest $request)
    {
        return $this->store->execute($request);
    }

    /**
     * Show Job Order
     * 
     * Display the specified resource.
     */
    public function show(JobOrder $jobOrder)
    {
        return $this->show->execute($jobOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobOrder $jobOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobOrder $jobOrder)
    {
        //
    }

    /**
     * Get Job Order Enums
     * 
     * Fetch enums for Job Order creation form
     */
    public function jobOrderEnums(JobOrderEnumsRequest $request) {
        $this->authorize('jobOrderEnums', JobOrder::class);

        return $this->enums->execute($request);
    }

    /**
     * Show Job Order Quotation
     * 
     * Fetch the quotation details associated with a Job Order
     */
    public function showJobOrderQuotation(JobOrder $jobOrder) {
        $this->authorize('showJobOrderQuotation', $jobOrder);

        return $this->showQuotation->execute($jobOrder);
    }

    /**
     * Accept Job Order
     * 
     * Accept a Job Order and assign it to the authenticated Operations user
     */
    public function acceptJobOrder(JobOrder $jobOrder) {
        $this->authorize('acceptJobOrder', $jobOrder);

        return $this->accept->execute($jobOrder);
    }

    /**
     * Request Job Order Reassignment
     * 
     * Request reassignment of the Job Order to another Operations user (Operations users) or request reassignment to Operations team (Lead Operations)
     */
    public function requestReassignment(JobOrder $jobOrder, RequestReassignmentRequest $request) {
        $this->authorize('requestReassignment', $jobOrder);

        return $this->requestReassignment->execute($jobOrder, $request);
    }

    /**
     * Reassign Job Order Operations
     * 
     * Reassign the Job Order to another Operations user
     */
    public function reassignOps(ReassignOpsRequest $request, JobOrder $jobOrder) {
        $this->authorize('reassignOps', $jobOrder);

        return $this->reassignOps->execute($request, $jobOrder);
    }
}
