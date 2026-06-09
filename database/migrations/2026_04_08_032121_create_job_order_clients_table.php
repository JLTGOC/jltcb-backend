<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\JobOrder;
use App\Models\ServiceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_order_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(JobOrder::class)->constrained()->onDelete('cascade');
            $table->string('client_type');
            $table->string('accredited');
            $table->foreignIdFor(ServiceType::class)->constrained()->onDelete('cascade');
            $table->string('tone_and_attitude')->nullable();
            $table->text('client_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_order_clients');
    }
};
