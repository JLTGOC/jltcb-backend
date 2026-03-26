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
        Schema::create('billing_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(JobOrder::class)->constrained()->cascadeOnDelete();
            $table->string('hs_code')->nullable();
            $table->string('permits')->nullable();
            $table->text('special_remarks')->nullable();
            $table->text('terms_of_payment')->nullable();
            $table->date('billing_date')->nullable();
            $table->string('shall_be_billed')->nullable();
            $table->text('closing_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_details');
    }
};
