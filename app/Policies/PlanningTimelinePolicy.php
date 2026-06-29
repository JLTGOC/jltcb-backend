<?php

namespace App\Policies;

use App\Models\JobOrder;
use App\Models\PlanningTimeline\Timeline\Timeline;
use App\Models\PlanningTimeline\Timeline\TimelineDocument;
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

    // ----------------------------
    // Planning Timeline Documents
    // ----------------------------

    /**
     * Determine whether the user can upload timeline document.
     */
    public function uploadDocument(User $user, Timeline $timeline)
    {
        $assignees = $timeline->phases
            ->flatMap->processes
            ->flatMap->tasks
            ->flatMap->assignees
            ->pluck('id')
            ->filter()
            ->unique('id')
            ->values()
            ->all();

        return in_array($user->id, $assignees);
    }

    /**
     * Determine whether the user can view all timeline documents.
     */
    public function viewAllDocuments(User $user, Timeline $timeline)
    {
        return $user->hasRole(['Client Success', 'Operations']);
    }

    /**
     * Determine whether the user can view uploaded file type enums.
     */
    public function availableFileTypes(User $user)
    {
        return $user->hasRole(['Client Success', 'Operations']);
    }

    /**
     * Determine whether the user can view uploaded file data used in show method.
     */
    public function viewDocumentData(User $user, Timeline $timeline, TimelineDocument $document)
    {
        return $user->hasRole(['Client Success', 'Operations']);
    }

    /**
     * Determine whether the user can view the actual uploaded file.
     */
    public function viewDocument(User $user, TimelineDocument $document)
    {
        return $user->hasRole(['Client Success', 'Operations']) || $document->uploaded_by === $user->id;
    }
}
