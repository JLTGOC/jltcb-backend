<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{
    Industry,
    Company,
};

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::factory()->count(9)->create();
        $jltcbCompany = Company::factory()->create([
            'name' => 'JLTCB',
            'business_type_id' => 1,
        ]);
        $companies->push($jltcbCompany);

        foreach ($companies as $company) {
            $industryIds = Industry::inRandomOrder()->take(rand(1, 3))->pluck('id')->toArray();
            $company->companyIndustries()->createMany(array_map(function ($industryId) {
                return ['industry_id' => $industryId];
            }, $industryIds));

            $companyAddress = null;
            if ($company->id === $jltcbCompany->id) {
                $companyAddress = 'Suite 508, Pacific Centre, 460 Quintin Paredes St. Brgy. 289 Binondo, Manila, Philippines 1006';
            }
            $company->address()->create([
                'registered_address' => $companyAddress ? $companyAddress : fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
                'office_address' => $companyAddress ? $companyAddress : fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode(),
                'usual_port' => fake()->city(),
                'origin_country' => fake()->country(),
                'destination_country' => fake()->country(),
            ]);
            $company->warehouseAddresses()->createMany([
                ['address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode()],
                ['address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode()],
            ]);
            $company->deliveryAddresses()->createMany([
                ['address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode()],
                ['address' => fake()->streetAddress() . ', ' . fake()->city() . ', ' . fake()->stateAbbr() . ' ' . fake()->postcode()],
            ]);

            $contactTypes = ['PRIMARY', 'SECONDARY', 'BILLING'];
            foreach ($contactTypes as $type) {
                $company->contacts()->create([
                    'full_name' => fake()->firstName() . ' ' . fake()->lastName(),
                    'position' => fake()->jobTitle(),
                    'email' => fake()->unique()->safeEmail(),
                    'contact_number' => fake()->numerify('09#########'),
                    'type' => $type,
                ]);
            }

            $company->representatives()->createMany([
                [
                    'full_name' => fake()->firstName() . ' ' . fake()->lastName(),
                ],
                [
                    'full_name' => fake()->firstName() . ' ' . fake()->lastName(),
                ],
            ]);

            $company->registration()->create([
                'tin' => fake()->unique()->numerify('TIN#########'),
                'bir_registration_number' => fake()->unique()->numerify('BRN#########'),
                'importer_accreditation_number' => fake()->unique()->numerify('IAN#########'),
                'importer_accreditation_expiry' => fake()->date(),
                'exporter_accreditation_number' => fake()->unique()->numerify('EAN#########'),
                'exporter_accreditation_expiry' => fake()->date(),
                'special_permits' => fake()->sentence(),
                'compliance_risk' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH']),
                'cprs_status' => fake()->randomElement(['ACTIVE', 'INACTIVE']),
            ]);

            $company->pricing()->create([
                'service_rate' => fake()->randomFloat(2, 1000, 10000),
                'special_discounts' => fake()->randomFloat(2, 0, 50),
                '3pl_profit_range' => fake()->randomFloat(2, 10, 30),
                'notes' => fake()->sentence(),
            ]);

            $company->operation()->create([
                'preferred_communication_style' => fake()->randomElement(['EMAIL', 'PHONE', 'MEETING']),
                'response_time_expectation' => fake()->randomElement(['IMMEDIATE', 'WITHIN 24 HOURS', 'WITHIN 48 HOURS']),
                'client_specific_sop' => fake()->sentence(),
                'approval_workflow' => fake()->sentence(),
                'pre_alert_details' => fake()->sentence(),
                'special_instructions' => fake()->sentence(),
            ]);

            $company->monitoring()->create([
                'past_issues' => fake()->sentence(),
                'penalties' => fake()->sentence(),
                'custom_flags' => fake()->sentence(),
                'payment_delays' => fake()->sentence(),
                'claims' => fake()->sentence(),
                'notes' => fake()->sentence(),
            ]);

            $company->insight()->create([
                'growth' => fake()->randomElement(['HIGH', 'MEDIUM', 'LOW']),
                'expansion_plan' => fake()->sentence(),
                'competitors' => fake()->sentence(),
                'opportunities' => fake()->sentence(),
                'notes' => fake()->sentence(),
            ]);

            $company->documents()->create([
                'file_name' => 'billOfLading.pdf',
                'file_type' => 'pdf',
                'filepath' => 'files/billOfLading.pdf',
            ]);
        }
    }
}
