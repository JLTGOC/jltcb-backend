<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RegulatoryAssistanceType;

class RegulatoryAssistanceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regulatoryAssistanceTypes = [
            'BEAUREAU OF CUSTOMS (BOC)',
            'PHILIPINE EXPORTERS CONFEDERATION, INC. (PHILEXPORT)',
            'PHILIPPINE ECONOMIC ZONE AUTHORITY (PEZA)',
            'DEPARTMENT OF FINANCE (DOF)',
            'FOOD AND DRUG ADMINISTRATION (FDA)',
            'BEAUREAU OF INTERNAL REVENUE (BIR)',
            'BEAUREAU OF ANIMAL INDUSTRY (BAI)',
            'NATIONAL MEAT INSPECTION SERVICE (NMIS)',
            'BEAUREAU OF FISHIERIES AND AQUATIC RESOURCES (BFAR)',
            'BEAUREAU OF AGRICULTURE AND FISHERIES STANDARDS (BAFS)',
            'NATIONAL TELECOMMUNICATIONS COMMISSION (NTC)',
            'OPTICAL MEDIA BOARD (OMB)',
            'DEPARTMENT OF TRADE AND INDUSTRY - BEAUREAU OF PRODUCT STANDARDS (DTI-BPS)',
            'SUGAR REGULATORY ADMINISTRATION (SRA)',
            'DANGEROUS DRUGS BOARD (DDB)',
            'THE PHILIPPINE DRUG ENFORCEMENT ADMINISTRATION (PDEA)',
        ];

        foreach ($regulatoryAssistanceTypes as $type) {
            RegulatoryAssistanceType::create(['name' => $type]);
        }
    }
}
