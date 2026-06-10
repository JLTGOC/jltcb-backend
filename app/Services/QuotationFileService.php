<?php

namespace App\Services;

use App\Http\Resources\QuotationFileResource;
use App\Models\Quotation;
use App\Models\QuotationFile;
use App\Models\QuotationFileChecklistItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QuotationFileService
{
    private const DIRECTORY = 'files';
    private const DISK = 'local';

    public function uploadQuotationFile(Quotation $quotation, UploadedFile $file, User $user) {
        $path = $file->store(self::DIRECTORY, self::DISK);

        $quotationFile = $quotation->files()->updateOrCreate(
            [
                'quotation_id' => $quotation->id,
                'type'         => 'PROPOSAL',
            ],
            [
                'file_path'          => $path,
                'uploaded_by'        => $user->id,
                'original_file_name' => $file['file']->getClientOriginalName(),
                'file_type'          => $file['file']->getClientOriginalExtension(),
                'document_checklist_item_id' => null,
            ]
        );

        $quotation->update([
            'status' => 'RESPONDED',
        ]);

        return $quotationFile;
    }   

    public function syncClientDocuments(
        Quotation $quotation,
        User $user,
        array $newFiles = [],
        array $removedFileIds = []
    )
    {
        try {
            // Remove files
            if (!empty($removedFileIds)) {
                $quotation->files()->whereIn('id', $removedFileIds)->get()->each(function($file) {
                    Storage::disk(self::DISK)->delete($file->file_path);
                    $file->delete();
                });
            }

            if (empty($newFiles)) {
                return true;
            }

            // Add new files
            foreach ($newFiles as $file) {
                $path = $file['file']->store(self::DIRECTORY, self::DISK);

                $quotation->files()->create([
                    'file_path'          => $path,
                    'uploaded_by'        => $user->id,
                    'type'               => 'REQUESTED',
                    'original_file_name' => $file['file']->getClientOriginalName(),
                    'file_type'          => $file['file']->getClientOriginalExtension(),
                    'document_checklist_item_id' => $file['type'] ? QuotationFileChecklistItem::where('name', $file['type'])->first()?->id : null,
                ]);
            }

            return true;
        } catch(\Exception $e) {
            return $e;
        }
    }   
}