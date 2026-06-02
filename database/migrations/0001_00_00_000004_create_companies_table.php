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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('consignee_used');
            $table->string('trade_name');
            // $table->foreignId('account_handler_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('transaction_type_id')->nullable()->constrained('transaction_types')->onDelete('cascade');
            $table->string('transaction_type_other')->nullable();
            $table->foreignId('company_type_id')->nullable()->constrained('company_types')->onDelete('cascade');
            $table->string('company_type_other')->nullable();
            $table->foreignId('client_classification_id')->nullable()->constrained('client_classifications')->onDelete('cascade');
            $table->string('client_classification_other')->nullable();
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->onDelete('cascade');
            $table->string('business_type_other')->nullable();
            $table->string('business_registration_number');
            $table->string('website');
            $table->unsignedBigInteger('years_in_operation');
            $table->date('activation_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
