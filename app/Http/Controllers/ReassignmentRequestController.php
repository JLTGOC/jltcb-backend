<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReassignmentRequest;

class ReassignmentRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ReassignmentRequest $reassignmentRequest)
    {
        if (!$reassignmentRequest) {
            return $this->error('Reassignment request not found', 404);
        }

        return $this->success('Reassignment request retrieved successfully', $reassignmentRequest, 200);
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
