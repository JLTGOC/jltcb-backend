<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\JobOrder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(JobOrder::class)->constrained()->onDelete('cascade');
            $table->string('service_level');
            $table->string('bl_no');
            $table->date('eta');
            $table->date('etd');
            $table->string('hs_code')->nullable();
            $table->string('rod')->nullable();
            $table->string('permits')->nullable();
            $table->text('shipment_remarks')->nullable();
            $table->string('target_delivery_date')->nullable();
            $table->string('target_completion_date')->nullable();
            $table->text('commitment_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_order_shipments');
    }
};
