<?php

namespace Database\Seeders;

use App\Models\Quotation;
use App\Models\QuotationFile;
use App\Models\User;
use Database\Seeders\Traits\SeederFileTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuotationFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    use SeederFileTrait;

    public function run(): void
    {
        $this->cleanupSeederFiles('files');

        $files = [
            ['clientDoc.pdf', 'QuotationFile.pdf'],  //for Quotation with RESPONDED status
            ['clientDoc1.pdf'],                      //for Quotation with REQUESTED only status
        ];

        $clients = User::role('Client')->take(2)->get();
        $AS      = User::role('Account Specialist')->first();

        foreach ($clients as $clientIndex => $client) {
            $status = $clientIndex === 0 ? 'RESPONDED' : 'REQUESTED';

            // Fetch existing quotation for this client with the required status
            $quotation = Quotation::where('client_id', $client->id)
                                ->where('status', $status)
                                ->first();

            if (!$quotation) {
                continue; 
            }

            // Attach files for this client’s quotation
            foreach ($files[$clientIndex] as $fileIndex => $file) {
                $filePath = str_replace('storage/', '', $this->copySeederFile('files', $file, disk: 'private'));

                QuotationFile::updateOrCreate([
                    'quotation_id' => $quotation->id,
                    'file_path' => $filePath,
                ], [
                    'uploaded_by'        => $fileIndex ? $AS->id : $client->id,
                    'quotation_id'       => $quotation->id,
                    'file_path'          => $filePath,
                    'type'               => $fileIndex ? 'PROPOSAL' : 'REQUESTED',
                    'original_file_name' => $fileIndex ? 'QUOTATION.pdf' : 'DOCUMENT.pdf',
                    'file_type' => 'pdf'
                ]);
            }
        }
    }
}
