<?php

namespace Database\Seeders;

use App\Models\Quotation;
use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateConfig\{
    BillingConfiguration,
    StandardConfiguration,
};
use Database\Seeders\Traits\SeederFileTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IssuedQuotationSeeder extends Seeder
{
    use SeederFileTrait;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quotations = Quotation::whereNotIn('status', ['REQUESTED', 'DISCARDED'])->get();

        foreach($quotations as $quotation) {
            $quotationTemplate = QuotationTemplate::find($quotation->has('regulatoryService') ? 2 : 1);
            $currency = fake()->randomElement(BillingConfiguration::currencies()->pluck('label'));
            $uom = fake()->randomElement(BillingConfiguration::uoms()->pluck('label'));

            $issuedQuotation = $quotation->issuedQuotations()->create([
                'template_id' => $quotationTemplate->id,
                'issued_by' => $quotation->as_id,
                'subject' => 'Sample Quotation Subject',
                'message' => 'Sample Quotation Message',
                'rate_validity' => fake()->dateTimeBetween('+3 days', '+3 months'),
                'currency'       => $currency,
                'uom'            => $uom,
            ]);

            $detailConfigs = $quotationTemplate->detailConfigs;
            foreach($detailConfigs as $detailConfig) {
                $issuedQuotation->detailValues()->create([
                    'label' => $detailConfig->label,
                    'value' => fake()->word()
                ]);
            }

            $templateCharges = $quotationTemplate->templateCharges;

            foreach($templateCharges as $templateCharge) {
                $charge = $issuedQuotation->charges()->create([
                    'name' => $templateCharge->name,
                ]);

                $allowedReceiptCharge = $templateCharge->allowedReceiptCharges()->pluck('label');
                $randomReceiptChargeCount = fake()->numberBetween(1, $allowedReceiptCharge->count());

                $randomReceiptCharges = fake()->randomElements($allowedReceiptCharge, $randomReceiptChargeCount);
                foreach($randomReceiptCharges as $randomReceiptCharge) {
                    $charge->items()->create([
                        'receipt_charge_label' => $randomReceiptCharge,
                        'amount'               => fake()->numberBetween(1000, 20000),
                    ]);
                }

                $charge->update([
                    'subtotal' => $charge->items()->sum('amount')
                ]);
            }

            $standardConfigTemplate = StandardConfiguration::first();

            $issuedQuotation->standardConfig()->create([
                'name' => $standardConfigTemplate->template_name, 
                'policies' => $standardConfigTemplate->policies, 
                'terms_and_conditions' => $standardConfigTemplate->terms_and_conditions, 
                'banking_details' => $standardConfigTemplate->banking_details, 
                'footer' => $standardConfigTemplate->footer
            ]);

            $issuedQuotation->authorizedSignatory()->create([
                'closing_statement' => fake()->sentence(),
                'is_authorized_signatory' => fake()->boolean(),
                'authorized_signatory_name' => fake()->name(),
                'position' => 'Lead Account Specialist',
                'signature_file_path' => $this->copySeederFile('images', 'signature.png', disk: 'local')
            ]);
        }
    }
}
