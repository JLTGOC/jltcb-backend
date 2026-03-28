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
        Schema::create('billing_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->enum('type', ['CURRENCY', 'UOM', 'RECEIPT CHARGES']);
            $table->timestamps();
        });

        Schema::create('details_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->enum('type', ['DROPDOWN', 'TEXT', 'DATE PICKER']);
            $table->timestamps();
        });

        Schema::create('config_dropdown_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('details_config_id');
            $table->foreign('details_config_id')->references('id')->on('details_configurations')->onDelete('cascade');
            $table->unique(['details_config_id', 'name']);
            $table->timestamps();
        });

        Schema::create('standard_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->unique();
            $table->text('policies');
            $table->text('terms_and_conditions');
            $table->text('banking_details');
            $table->text('footer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
