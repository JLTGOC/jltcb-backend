<?php

namespace Database\Seeders;

use App\Models\BillingConfiguration;
use App\Models\ConfigDropdownOption;
use App\Models\DetailsConfiguration;
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
            'PHP', 'USD', 'INR', 'JPY'
        ];

        $uom = ['PER APP', 'PER BL', 'PER CONTAINER'];

        $textInput = ['SERVICE LEVEL', 'PAYMENT TERMS', 'SUBJECT'];

        $datePicker = ['RATE VALIDITY'];

        $dropdowns = [
            'TYPE OF ACCREDITATION' => ['NEW', 'RENEWAL'],
            'APPROVAL STATUS' => ['APPROVED', 'DENIED'],
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
            'DATE PICKER' => $datePicker,
            'DROPDOWN' => array_keys($dropdowns)
        ]);

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
}
