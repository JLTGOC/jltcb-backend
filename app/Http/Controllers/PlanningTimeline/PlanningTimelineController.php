<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanningTimeline\AssignTimelineTaskRequest;
use App\Http\Requests\PlanningTimeline\StorePlanningTimelineRequest;
use App\Http\Resources\PlanningTimeline\Timeline\TimelineResource;
use App\Models\JobOrder;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Timeline\Timeline;
use App\Services\PersonInChargeService;
use App\Services\PlanningTimelineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanningTimelineController extends Controller
{
    public function __construct(private readonly PlanningTimelineService $timelineService, private readonly PersonInChargeService $personInChargeService)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store Planning Timeline
     * 
     * NOTE: 
     * Default phase headings are identified using the key field, which serves as the mapping reference for default headings within each phase. Headings are stored as static  values rather than database identifiers. 
     * When submitting or updating phase headings, all required default heading keys must be included to ensure data integrity and proper system mapping.
     * The list of valid heading keys is returned in the phases.headings section of the API response.
     */
    public function store(StorePlanningTimelineRequest $request, JobOrder $jobOrder)
    {
        $this->timelineService->ensureJobOrderIsAccepted($jobOrder);

        $this->authorize('create', [Timeline::class, $jobOrder]);

        if (Timeline::where('job_order_id', $jobOrder->id)->exists()) {
            return $this->error('A Planning & Timeline already exists for this job order', statusCode: 409);
        }

        if ($request->input('planning_template_id', null)) {
            $applicableTemplateId = PlanningTemplate::where('id', $request->input('planning_template_id'))
                ->where('service_category', $jobOrder->quotation->serviceCategory())->exists();

            if (!$applicableTemplateId) {
                return $this->error("The planning template id is invalid for the job order's service category", statusCode: 422);
            }
        }

        $timeline = $this->timelineService->create($jobOrder, $request->validated(), $request->user());

        return $this->success(
            'Planning and Timeline created successfully',
            new TimelineResource($timeline),
            201
        );
    }

    /**
     * Show Planning Timeline
     * 
     * NOTE: Inside response payload, values is a dynamic object where each key is a heading key and each value is the task’s data for that field.
     */
    public function show(JobOrder $jobOrder, Timeline $timeline)
    {
        $this->authorize('view', [Timeline::class, $jobOrder, $timeline]);

        $timeline = $this->timelineService->loadForView($timeline);

        return $this->success(
            'Planning timeline fetched successfully',
            new TimelineResource($timeline)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Show Persons In Charge
     * 
     * Fetches list of persons-in-charge for accomplishing planning timeline tasks. Defaults to Operations users if no roles provided in request.
     */
    public function getAssignees(Request $request) {
        $this->authorize('getAssignees', [Timeline::class]);

        $request->validate([
            'roles' => ['sometimes', 'nullable'],
            'roles.*' => ['required', Rule::in(RoleType::cases())]
        ]);

        $roles = [RoleType::OPERATIONS];
        if ($request->input('roles')) {
            $roles = $request->input('roles');
        }

        return $this->success(
            'Planning timeline persons-in-charge fetched successfully',
            [
                'users' => $this->personInChargeService->getPersonsInCharge($roles, $request->input('search', null))
            ] 
        );
    }

    /**
     * Assign Timeline Tasks
     * 
     * Selects persons-in-charge to be assigned to planning timeline tasks.
     */
    public function assignTasks(AssignTimelineTaskRequest $request, Timeline $timeline) {
        $this->authorize('assignTasks', [Timeline::class, $timeline]);

        $timeline = $this->timelineService->assignTasks($timeline, $request->validated());
        return $this->success(
            'Planning Timeline tasks person-in-charge assigned successfully',
            new TimelineResource($timeline), 
            201
        );
    }


}
