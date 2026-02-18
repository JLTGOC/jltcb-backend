<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Quotation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignIdFor(Quotation::class)->constrained();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('users')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('as_id');
            $table->foreign('as_id')->references('id')->on('users')->constrained();
            $table->enum('status', ['ONGOING', 'DELIVERED'])->default('ONGOING');
            $table->string('contact_person');
            $table->string('contact_number');
            $table->string('email');
            $table->string('company_name');
            $table->string('commodity');
            $table->enum('cargo_type', ['CONTAINERIZED', 'LCL'])->default('CONTAINERIZED');
            // $table->unsignedBigInteger('cargo_volume')->nullable();
            $table->string('container_size')->nullable();
            $table->string('origin');
            $table->string('destination');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
