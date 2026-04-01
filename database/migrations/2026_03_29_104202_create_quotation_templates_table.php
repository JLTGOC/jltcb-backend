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
        Schema::create('quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('service_type', ['REGULATORY','LOGISTICS']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Billing Details Section template
        Schema::create('template_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('quotation_templates')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('template_charge_receipt_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_charge_id')->constrained('template_charges')
                ->cascadeOnDelete();
            $table->foreignId('billing_config_id')->constrained('billing_configurations')
                ->cascadeOnDelete();
            $table->unique(['template_charge_id', 'billing_config_id'], 'allowed_options_unique');
            $table->timestamps();
        });

        // Quotation Details Section template
        Schema::create('template_detail_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('quotation_templates')
                ->cascadeOnDelete();
            $table->foreignId('details_config_id')->constrained('details_configurations')
                ->cascadeOnDelete();
            $table->unique(['template_id', 'details_config_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_templates');
    }
};
