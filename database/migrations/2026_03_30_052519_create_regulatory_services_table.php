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
        Schema::create('regulatory_services', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Quotation::class)->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('contact_person_contact_number')->nullable();
            $table->string('business_type');
            $table->string('position');
            $table->string('type_of_regulatory_assistance');
            $table->string('application_type');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulatory_services');
    }
};
