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
        Schema::create('company_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('tin')->unique();
            $table->string('bir_registration_number')->unique();
            $table->enum('cprs_status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->string('importer_accreditation_number')->nullable()->unique();
            $table->string('exporter_accreditation_number')->nullable()->unique();
            $table->date('importer_accreditation_expiry')->nullable();
            $table->date('exporter_accreditation_expiry')->nullable();
            $table->string('special_permits')->nullable();
            $table->string('compliance_risk')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_registrations');
    }
};
