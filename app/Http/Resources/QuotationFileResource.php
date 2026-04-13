<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class QuotationFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uploaded_by' => $this->uploaded_by,
            'quotation_id' => $this->quotation_id,
            'file_url' => URL::temporarySignedRoute('files.view', Carbon::now()->addMinutes(10), [
                    'file' => $this->id
                ]),
            'file_type' => $this->file_type,
            'type' => $this->type,
            'file_name' => $this->original_file_name,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
