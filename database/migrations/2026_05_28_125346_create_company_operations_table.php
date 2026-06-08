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
        Schema::create('company_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('preferred_communication_style')->nullable();
            $table->string('decision_making_process')->nullable();
            $table->string('response_time_expectation')->nullable();
            $table->string('client_specific_sop')->nullable();
            $table->string('approval_workflow')->nullable();
            $table->string('pre_alert_details')->nullable();
            $table->string('special_instructions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_operations');
    }
};
