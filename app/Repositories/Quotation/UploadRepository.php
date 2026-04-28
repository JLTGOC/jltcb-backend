<?php

namespace App\Repositories\Quotation;

use App\Http\Resources\QuotationFileResource;
use App\Repositories\BaseRepository;
use App\Services\QuotationFileService;
use Illuminate\Support\Facades\DB;

class UploadRepository extends BaseRepository
{
    protected $quotationFileService;

    public function __construct(QuotationFileService $quotationFileService)
    {
        $this->quotationFileService = $quotationFileService;
    }

    public function execute($quotation, $request){
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,xls,xlsx']
        ]);

        DB::beginTransaction();
        try {

            $quotationFile = $this->quotationFileService->uploadQuotationFile(
                $quotation, $request->file('file'), $request->user()
            );
            
            $message = $quotationFile->wasRecentlyCreated 
                ? 'Quotation file uploaded successfully' 
                : 'Quotation file updated sucessfully';

            $status = $quotationFile->wasRecentlyCreated ? 201 : 200;

            DB::commit();
            return $this->success($message, new QuotationFileResource($quotationFile), $status);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(
                'Failed to upload quotation file', 500, $e->getMessage()
            );
        }
    }
}
