<?php

namespace Database\Factories;

use App\Models\{
    User,
    TransactionType,
    CompanyType,
    BusinessType,
    ClientClassification,
    Company
};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'consignee_used' => $this->faker->company(),
            'trade_name' => $name,
            'account_handler_id' => null,
            'transaction_type_id' => TransactionType::inRandomOrder()->first()->id,
            'company_type_id' => CompanyType::inRandomOrder()->first()->id,
            'client_classification_id' => ClientClassification::inRandomOrder()->first()->id,
            'business_type_id' => BusinessType::inRandomOrder()->first()->id,
            'business_registration_number' => $this->faker->unique()->numerify('BRN########'),
            'website' => $this->faker->url(),
            'years_in_operation' => $this->faker->numberBetween(1, 50),
            'activation_date' => $this->faker->date(),
        ];
    }
}
