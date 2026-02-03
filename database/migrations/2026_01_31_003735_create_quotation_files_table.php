<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_file_name');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->enum('type', ['REQUESTED', 'PROPOSAL'])->default('REQUESTED');
            $table->timestamps();
            $table->unique(['quotation_id', 'uploaded_by']);
        });

        //shipment id
        //quote file id
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_files');
    }
};
