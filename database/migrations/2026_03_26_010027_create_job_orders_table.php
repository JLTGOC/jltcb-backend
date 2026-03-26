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
            $table->string('job_type');
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('as_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operations_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('finance_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('subject');
            $table->text('email_body');
            $table->string('client_type');
            $table->string('accredited');
            $table->string('tone_and_attitude')->nullable();
            $table->text('remarks')->nullable();
            $table->string('service_level');
            $table->string('bl_no');
            $table->date('eta');
            $table->date('etd');
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
