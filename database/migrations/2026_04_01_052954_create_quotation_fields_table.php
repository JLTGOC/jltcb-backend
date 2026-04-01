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
        Schema::create('quotation_fields', function (Blueprint $table) {
            $table->id();
            $table->enum('quotation_type', ['REGULATORY', 'LOGISTICS']);
            $table->string('field_name');
            $table->string('display_name');
            $table->timestamps();
        });

        Schema::create('template_client_input_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('quotation_templates')
                ->cascadeOnDelete();
            $table->foreignId('quotation_field_id')->constrained('quotation_fields')
                ->cascadeOnDelete();
            $table->unique(['template_id', 'quotation_field_id'], 'allowed_client_info_unique');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_fields');
    }
};
