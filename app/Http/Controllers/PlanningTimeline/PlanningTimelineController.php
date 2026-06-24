<?php

namespace App\Http\Controllers\PlanningTimeline;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanningTimeline\Timeline\TimelineResource;
use App\Models\JobOrder;
use App\Models\PlanningTimeline\Timeline\Timeline;
use Illuminate\Http\Request;

class PlanningTimelineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Create Planning Timeline
     * 
     * create a planning timeline for a job order.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show Planning Timeline
     * 
     * NOTE: Inside response payload, values is a dynamic object where each key is a heading key and each value is the task’s data for that field.
     */
    public function show(JobOrder $jobOrder, Timeline $timeline)
    {
        $timeline->load([
            'phases.processes.tasks.values.phaseHeading',
            'phases.processes.tasks.assignees',
            'phases.headings'
        ]);

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
}
