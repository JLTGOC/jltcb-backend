<?php

namespace App\Policies;

use App\Models\JobOrder;
use App\Models\PlanningTimeline\Timeline\Timeline;
use App\Models\User;

class PlanningTimelinePolicy
{
    /**
     * Allow only ops user who accepted the job order to create a timeline.
     */
    public function create(User $user, JobOrder $jobOrder)
    {
        return $user->id === $jobOrder->operations_id;
    }
    
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobOrder $jobOrder, Timeline $timeline)
    {
        return $user->hasrole(['Client Success', 'Operations']);
    }

    /**
     * Determine whether the user can view the list of persons in charge.
     */
    public function getAssignees(User $user)
    {
        return $user->hasRole(['Client Success', 'Operations']);
    }

    /**
     * Determine whether the user can assign timeline tasks.
     */
    public function assignTasks(User $user, Timeline $timeline)
    {
        return $user->id === $timeline->created_by;
    }
}
