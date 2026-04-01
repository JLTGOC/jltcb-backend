<?php

namespace Database\Seeders;

use App\Models\QuotationField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QuotationFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            'logistics_services' => 'LOGISTICS',
            'regulatory_services' => 'REGULATORY'
        ];

        $excluded = ['id', 'quotation_id', 'created_at', 'updated_at'];

        foreach($tables as $tableName => $tableType) {
            $columns = array_diff(Schema::getColumnListing($tableName), $excluded);

            foreach($columns as $column) {
                QuotationField::create([
                    'quotation_type' => $tableType,
                    'field_name' => $column,
                    'display_name' => Str::headline($column)
                ]);
            }
        }
    }
}
