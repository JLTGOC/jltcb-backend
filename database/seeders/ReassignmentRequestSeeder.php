<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{
    ReassignmentRequest,
    Quotation,
    JobOrder,
}; 

class ReassignmentRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $additionalRequests = ReassignmentRequest::factory()->count(10)->create();

        $quotations = Quotation::where('assignment_status', 'REASSIGNMENT REQUESTED')->get();
        
        foreach ($quotations as $quotation) {
            ReassignmentRequest::create([
                'quotation_id' => $quotation->id,
                'as_id' => $quotation->as_id,
                'reason' => fake()->randomElement(['WORKLOAD', 'EMERGENCY / LEAVE', 'CLIENT REQUEST']),
                'status' => 'PENDING',
                'additional_details' => fake()->sentence(),
            ]);
        }

        $jobOrders = JobOrder::where('assignment_status', 'REASSIGNMENT REQUESTED')->get();

        foreach ($jobOrders as $jobOrder) {
            ReassignmentRequest::create([
                'job_order_id' => $jobOrder->id,
                'ops_id' => $jobOrder->operations_id,
                'reason' => fake()->randomElement(['WORKLOAD', 'EMERGENCY / LEAVE', 'CLIENT REQUEST']),
                'status' => 'PENDING',
                'additional_details' => fake()->sentence(),
            ]);
        }
    }
}
