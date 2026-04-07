<?php

namespace Database\Seeders;

use App\Models\BillingConfiguration;
use App\Models\DetailsConfiguration;
use App\Models\QuotationField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuotationTemplate;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $template = QuotationTemplate::create([
            'name' => 'ACCREDITATION',
            'service_type' => 'LOGISTICS',
            'is_active' => true
        ]);

        $detailConfigIds = DetailsConfiguration::all()->pluck('id')->toArray();
        $template->detailConfigs()->attach($detailConfigIds);

        $charges = ['THIRD-PARTY RECEIPT CHARGES', 'JLTCB SERVICE CHARGES'];

        foreach($charges as $charge) {
            $templateCharge = $template->templateCharges()->create([
                'name' => $charge
            ]);

            $receiptChargeIds = BillingConfiguration::receiptCharges()->get()->pluck('id')->toArray();
            $receiptChargesCount = count($receiptChargeIds);

            $templateCharge->allowedReceiptCharges()->attach(fake()->randomElements($receiptChargeIds, random_int(2, $receiptChargesCount)));
        }

        $quotationFields = QuotationField::logisticsFields()->pluck('id')->toArray();

        $template->quotationFields()->attach(
            fake()->randomElements($quotationFields, random_int(2, count($quotationFields)))
        );
    }
}
