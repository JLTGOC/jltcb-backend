<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationResource;
use App\Models\ReassignmentRequest;
use App\Models\User;
use App\Repositories\BaseRepository;
use Carbon\Carbon;

class ReassignSpecialistRepository extends BaseRepository
{
    public function execute($quotation, $request){
        $validated = $request->validated();

        $reassignmentRequest = ReassignmentRequest::where('quotation_id', $quotation->id)->where('status', 'PENDING')->latest()->first();

        if (!$reassignmentRequest) {
            return $this->error('No pending reassignment request for this quotation', 422);
        }

        if ($validated['status'] === 'REJECTED') {
            $quotation->update([
                'assignment_status' => 'ASSIGNED',
            ]);

            $reassignmentRequest->update([
                'status' => 'REJECTED'
            ]);

            return $this->success('Reassignment request rejected, previous Account Specialist retained', $reassignmentRequest, 200);
        } elseif ($validated['status'] === 'APPROVED') {
            $user = User::find($validated['as_id']);

            if (!$user->hasRole('Account Specialist')) {
                return $this->error('The selected user must have an Account Specialist role.', 422);
            }
            if ((int) $validated['as_id'] === $quotation->as_id) {
                return $this->error('The selected Account Specialist is already assigned to this quotation.', 422);
            }

            $quotation->update([
                'as_id' => $validated['as_id'],
                'assignment_status' => 'ASSIGNED',
                'assigned_at' => Carbon::now()
            ]);

            $reassignmentRequest->update([
                'status' => 'APPROVED'
            ]);

            return $this->success('Account Specialist reassigned successfully', new QuotationResource($quotation), 200);
        }
    }
}
