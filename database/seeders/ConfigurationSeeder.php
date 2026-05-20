<?php

namespace Database\Seeders;

use App\Models\BillingConfiguration;
use App\Models\DetailsConfiguration;
use App\Models\StandardConfiguration;
use App\Models\MessageTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recieptCharges = [
            'BUREAU OF CUSTOMS ACCREDITATION FEE',
            'CERTIFICATE OF ACCREDITATION FEE',
            'PHILEXPORT ACCREDITATION FEE',
            'DUTIES AND TAXES PAYABLE TO BOC',
            'WAREHOUSE AND STORAGE CHARGES',
            'CDEC LODGEMENT RECEIPT',
            'BROKERAGE FEE'
        ];

        $currency = [
            'PHP', 'USD'
        ];

        $uom = ['PER APP', 'PER BL', 'PER CONTAINER'];

        $textInput = ['SERVICE LEVEL', 'PAYMENT TERMS'];

        // $datePicker = ['RATE VALIDITY'];

        $dropdowns = [
            'TYPE OF ACCREDITATION' => ['NEW', 'RENEWAL'],
            'BUSINESS TYPE' => [
                'COOPERATIVE', 'CORPORATION', 'E-COMMERCE', 'INDIVIDUAL IMPORTER', 'GOVERNMENT AGENCY', 'IMPORT-EXPORT AGENCY', 'MULTINATIONAL COMPANY', 'NON-PROFIT ORGANIZATION', 'PARTNERSHIP', 'PEZA-REGISTERED ENTERPRISE', 'SOLE PROPREITORSHIP'
            ]
        ];

        $this->createConfigValues(BillingConfiguration::class, [
            'RECEIPT CHARGES' => $recieptCharges,
            'CURRENCY' => $currency,
            'UOM' => $uom
        ]);

        $this->createConfigValues(DetailsConfiguration::class, [
            'TEXT' => $textInput,
            // 'DATE PICKER' => $datePicker,
            'DROPDOWN' => array_keys($dropdowns)
        ]);

        // Details Configuration dropdown options
        foreach ($dropdowns as $label => $values) {
            $record = DetailsConfiguration::where('label', $label)->first();

            if ($record) {
                foreach ($values as $value) {
                    $record->dropdownOptions()->create([
                        'name' => $value
                    ]);
                }
            }
        }

        $this->seedStandardConfigRecords();
        $this->seedMessageTemplateRecords();
    }

    private function createConfigValues(string $modelClass, array $configGroupType) {
        foreach($configGroupType as $type => $values) {
            foreach($values as $value) {
                $modelClass::create([
                    'type' => $type,
                    'label' => $value,
                ]);
            }
        }
    }

    private function seedStandardConfigRecords() {
        StandardConfiguration::create([
            'template_name' => "Accreditation Template 1",
            'policies' => 
                "By signing this proposal, you confirm that you have read and understood the Terms and Conditions of this proposal and agree to be bound by them. You also acknowledge and agree to comply with all terms and conditions outlined in this Proposal, as well as any applicable laws and regulations. This proposal holds binding authority, with potential legal enforceability if any provision of this proposal is/are not fulfilled.",
            'terms_and_conditions' => 
                "1. The service charge shall include: a. Registration as exporter in Client Profile Registration System (CPRS); b. Processing of Bureau of Customs and PhilExport order of payment and payment of accreditation fee; and c. Submission of documents in Bureau of Customs and PhilExport for import and export license processing
2. All Bureau of Customs and PhilExport receipted charges shall be under the account of applicant nd is included in the rate above.
3. All documentary requirements for submission to the Bureau of Customs and PhilExport shall be the responsibility of the applicant.
4. The processing time shall commence from the receipt of complete documents.
5. The processing timeframe shall be 120-184 working days for PhilExport export license provided that the submitted documents have passed the pre-assessment stage. 6. Any incidental charges which shall accrue due to discrepancy of documents submitted to the government agency shall be billed but with prior notice.
7. The service charge is VAT ex and have no hidden charges. Terms of payment shall be 45 days upon service rendered.
8. For payments to Jill L. Tolentino Customs Brokerage, please deposit payment to the following account and email scanned copy or photo of deposit slip to finance@jltcb.com.",
            "banking_details" => 
                "Bank Name: Metropolitan Bank and Trust Company (Metrobank)
Account Name: Jill L. Tolentino Customs Brokerage
Account Number: 250 3 25008759 5
Type of Account: Savings
Branch: Ongpin 7345201
Swift Code: MBTCPHMM",
            'footer' => "CHILL WE GOT YOU!"
        ]);
    }

    private function seedMessageTemplateRecords() {
        MessageTemplate::insert([
            [
                'template_name' => 'MSG 1',
                'message' => 'Thank you for your for considering Jill L. Tolentino Customs Brokerage for your accreditation requirement.
We are pleased to offer our rate proposal for the accreditation of your company as a new exporter with PhilExport',
            ],
            [
                'template_name' => 'MSG 2',
                'message' => "We appreciate your interest in Jill L. Tolentino Customs Brokerage for your accreditation requirements.
Kindly find our proposed rates for assisting your company in securing accreditation as a new exporter with PhilExport.
",
            ],
            [
                'template_name' => 'MSG 3',
                'message' => 'Greetings from Jill L. Tolentino Customs Brokerage, and thank you for considering our services for your accreditation needs.
We are pleased to submit our rate proposal for your company’s registration as a new exporter with PhilExport.',
            ],
        ]);
    }
}
