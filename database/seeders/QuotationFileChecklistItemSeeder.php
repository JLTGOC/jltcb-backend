<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuotationFileChecklistItem;

class QuotationFileChecklistItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            ['name' => 'BILL OF LADING / AIRWAY BILL', 'visibility' => 'LOGISTICS'],
            ['name' => 'CERTIFICATE OF ORIGIN', 'visibility' => 'REGULATORY'],
            ['name' => 'COMMERCIAL INVOICE', 'visibility' => 'BOTH'],
            ['name' => 'PACKING LIST', 'visibility' => 'LOGISTICS'],
            ['name' => 'PROOF OF PAYMENT', 'visibility' => 'BOTH']
        ];

        foreach ($documents as $document) {
            QuotationFileChecklistItem::create($document);
        }
    }
}
