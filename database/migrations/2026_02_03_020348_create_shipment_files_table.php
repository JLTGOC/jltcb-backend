<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\{
    Shipment,
    QuotationFile
};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipment_files', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Shipment::class)->cascadeOnDelete();
            $table->foreignIdFor(QuotationFile::class)->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_files');
    }
};
