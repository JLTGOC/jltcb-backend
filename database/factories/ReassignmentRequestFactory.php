<?php

namespace Database\Factories;

use App\Models\ReassignmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{
    Quotation,
    JobOrder,
    User
};

/**
 * @extends Factory<ReassignmentRequest>
 */
class ReassignmentRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['quotation', 'job_order']);
        if ($type === 'quotation') {
            $quotation = Quotation::where('assignment_status', 'ASSIGNED')->inRandomOrder()->first();

            $status = $this->faker->randomElement(['APPROVED', 'REJECTED']);
            if ($status === 'APPROVED') {
                $asId = $this->faker->randomElement(User::role('Account Specialist')->whereNot('id', $quotation->as_id)->pluck('id'));
            } elseif ($status === 'REJECTED') {
                $asId = $quotation->as_id;
            }

            return [
                'quotation_id' => $quotation->id,
                'as_id' => $asId,
                'reason' => $this->faker->randomElement(['WORKLOAD', 'EMERGENCY / LEAVE', 'CLIENT REQUEST']),
                'additional_details' => $this->faker->sentence(),
                'status' => $status,
            ];
        } elseif ($type === 'job_order') {
            $jobOrder = JobOrder::where('assignment_status', 'ASSIGNED')->inRandomOrder()->first();

            $status = $this->faker->randomElement(['APPROVED', 'REJECTED']);
            if ($status === 'APPROVED') {
                $opsId = $this->faker->randomElement(User::role('Operations')->whereNot('id', $jobOrder->operations_id)->pluck('id'));
            } elseif ($status === 'REJECTED') {
                $opsId = $jobOrder->operations_id;
            }

            return [
                'job_order_id' => $jobOrder->id,
                'ops_id' => $opsId,
                'reason' => $this->faker->randomElement(['WORKLOAD', 'EMERGENCY / LEAVE', 'CLIENT REQUEST']),
                'status' => $status,
                'additional_details' => $this->faker->sentence(),
            ];
        }
    }
}
