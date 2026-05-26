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
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->enum('job_type', ['LOGISTICS', 'REGULATORY'])->default('LOGISTICS');
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('as_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operations_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('subject');
            $table->text('email_body');
            $table->date('date_issued')->nullable();
            $table->enum('shipment_creation_status', ['PENDING', 'CREATED'])->default('PENDING');
            $table->enum('assignment_status', ['AVAILABLE', 'ASSIGNED', 'REASSIGNMENT REQUESTED'])->default('AVAILABLE');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
