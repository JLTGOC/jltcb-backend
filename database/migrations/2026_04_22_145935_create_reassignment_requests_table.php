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
        Schema::create('reassignment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('job_order_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('as_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('ops_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->text('additional_details')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reassignment_requests');
    }
};
