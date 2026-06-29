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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('users')->constrained()->cascadeOnDelete();
            $table->string('client_name');
            $table->unsignedBigInteger('as_id')->nullable();
            $table->foreign('as_id')->references('id')->on('users')->constrained();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['REQUESTED', 'RESPONDED', 'ACCEPTED', 'DISCARDED'])->default('REQUESTED');
            $table->longText('service_options');
            $table->string('commodity');
            $table->string('contact_person')->nullable();
            $table->string('contact_number');
            $table->string('email');
            $table->string('company_name');
            $table->string('company_address');
            $table->string('position')->nullable();
            $table->string('consignee')->nullable();
            $table->enum('assignment_status', ['AVAILABLE', 'ASSIGNED', 'REASSIGNMENT REQUESTED'])->default('AVAILABLE');
            $table->timestamp('assigned_at')->nullable();
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
