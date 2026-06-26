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
        Schema::create('planning_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->unique()->constrained('job_orders')
                ->restrictOnDelete();
            $table->foreignId('planning_template_id')->nullable()->nullOnDelete()
                ->constrained('planning_templates');
            $table->foreignId('created_by')->nullable()->nullOnDelete()->constrained('users');
            $table->timestamps();
        });

        Schema::create('planning_timeline_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_timeline_id')->constrained('planning_timelines')
                ->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('planning_timeline_phase_headings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_phase_id')->constrained('planning_timeline_phases')
                ->cascadeOnDelete();
            $table->string('key')->nullable();
            $table->string('name');
            $table->enum('input_type', ['TEXT', 'NUMBER', 'DATETIME'])->default('TEXT');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['timeline_phase_id', 'key'], 'timeline_heading_key_unique');
            $table->unique(['timeline_phase_id', 'name'], 'timeline_heading_name_unique');
        }); 

        Schema::create('planning_timeline_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_phase_id')->constrained('planning_timeline_phases')
                ->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('planning_timeline_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_process_id')
                ->constrained('planning_timeline_processes')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_complete')->default(false);
            $table->timestamps();
        });

        Schema::create('planning_timeline_task_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_task_id')
                ->constrained('planning_timeline_tasks')->cascadeOnDelete();
            $table->foreignId('timeline_phase_heading_id')
                ->constrained('planning_timeline_phase_headings')
                ->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['timeline_task_id', 'timeline_phase_heading_id'],
                'task_value_unique'
            );
        });

        Schema::create('planning_timeline_task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_task_id')->constrained('planning_timeline_tasks')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['timeline_task_id', 'user_id'], 'task_assignee_unique');
        });

        Schema::create('planning_timeline_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_timeline_id')->constrained('planning_timelines')
                ->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('type');
            $table->enum('status', ['UPLOADED']);
            $table->string('file_path');
            $table->string('file_type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_timelines');
    }
};
