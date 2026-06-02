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
        Schema::create('issued_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('quotation_templates')
                ->nullOnDelete();
            $table->foreignId('issued_by')->constrained('users');
            $table->string('subject');
            $table->text('message');
            $table->date('rate_validity');
            $table->string('currency');
            $table->timestamps();
        });

        Schema::create('issued_quotation_detail_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_quotation_id')
                ->constrained('issued_quotations')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('issued_quotation_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_quotation_id')
                ->constrained('issued_quotations')
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('issued_quotation_charge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_quotation_charge_id')
                ->constrained('issued_quotation_charges')
                ->cascadeOnDelete();
            $table->string('receipt_charge_label');
            $table->unsignedInteger('quantity')->nullable();
            $table->string('container_size')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('uom');
            $table->timestamps();
        });

        Schema::create('issued_quotation_standard_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_quotation_id')
                ->constrained('issued_quotations')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('policies');
            $table->text('terms_and_conditions');
            $table->text('banking_details');
            $table->string('footer');
            $table->timestamps();
        });

        Schema::create('authorized_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_quotation_id')
                ->constrained('issued_quotations')
                ->cascadeOnDelete();
            $table->string('closing_statement');
            $table->boolean('is_authorized_signatory');
            $table->string('authorized_signatory_name');
            $table->string('position');
            $table->string('signature_file_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issued_quotations');
    }
};
