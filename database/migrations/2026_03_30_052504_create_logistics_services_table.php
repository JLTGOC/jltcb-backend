<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\{
    Quotation
};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logistics_services', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Quotation::class)->constrained()->onDelete('cascade');
            // $table->enum('service_type', ['IMPORT', 'EXPORT'])->default('IMPORT');
            $table->enum('transport_mode', ['AIR', 'SEA'])->default('AIR');
            $table->string('service_options');
            $table->string('commodity');
            $table->enum('cargo_type', ['CONTAINERIZED', 'LCL'])->default('CONTAINERIZED');
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
        Schema::dropIfExists('logistics_services');
    }
};
