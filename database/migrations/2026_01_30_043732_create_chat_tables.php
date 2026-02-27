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
        // 1. CONVERSATIONS
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['DIRECT', 'GROUP'])->default('DIRECT');
            $table->string('name')->nullable(); // For Group names like "OPS: IM-09-2025"
            $table->timestamp('last_message_at')->nullable()->index(); // For sorting inbox
            $table->timestamps();
        });

        // 2. PARTICIPANTS (Pivot Table)
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['ADMIN', 'MEMBER'])->default('MEMBER');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            
            $table->unique(['conversation_id', 'user_id']);
        });

        // 3. MESSAGES (With Polymorphism for Quotation Cards)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete(); // Nullable for System messages
            $table->text('content')->nullable(); // Text content
            
            // Types: TEXT, IMAGE, FILE, SYSTEM, QUOTATION_CARD
            $table->string('type')->default('TEXT'); 

            $table->string('file_name')->nullable();
            $table->string('attachment_path')->nullable();
            
            // This links to your Quotation model (reference_id, reference_type)
            $table->nullableMorphs('reference'); 
            
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_tables');
    }
};
