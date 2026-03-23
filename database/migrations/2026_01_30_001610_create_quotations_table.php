<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('users')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('as_id');
            $table->foreign('as_id')->references('id')->on('users')->constrained();
            $table->enum('status', ['REQUESTED', 'RESPONDED'])->default('REQUESTED');
            $table->string('contact_person');
            $table->string('contact_number');
            $table->string('email');
            $table->string('company_name');
            $table->string('company_address');
            $table->enum('service_type', ['IMPORT', 'EXPORT', 'BUSINESS SOLUTION'])->default('IMPORT');
            $table->enum('transport_mode', ['AIR', 'SEA'])->default('AIR');
            $table->string('service_options');
            $table->string('commodity');
            $table->enum('cargo_type', ['CONTAINERIZED', 'LCL'])->default('CONTAINERIZED');
            // $table->unsignedBigInteger('cargo_volume')->nullable();
            $table->string('container_size')->nullable();
            $table->string('origin');
            $table->string('destination');
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
