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
        Schema::create('planning_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('version_number')->default(1);
            $table->enum('service_category', ['REGULATORY', 'LOGISTICS']);
            $table->foreignId('service_type_id')->constrained('service_types')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('planning_template_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version_number')->default(1);
            $table->enum('service_category', ['REGULATORY', 'LOGISTICS']);
            $table->timestamps();

            $table->index('service_category');
            $table->unique('service_category');
        });

        Schema::create('planning_config_phases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('config_id')->constrained('planning_template_configs')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('planning_config_processes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('config_id')->constrained('planning_template_configs')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('planning_config_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('config_id')->constrained('planning_template_configs')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('planning_template_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_template_id')
                ->constrained('planning_templates')
                ->cascadeOnDelete();
            $table->foreignId('config_phase_id')
                ->constrained('planning_config_phases')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['planning_template_id', 'config_phase_id'], 'template_phase_unique');
        });

        Schema::create('planning_template_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_phase_id')
                ->constrained('planning_template_phases')
                ->cascadeOnDelete();
            $table->foreignId('config_process_id')
                ->constrained('planning_config_processes')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['template_phase_id', 'config_process_id'], 'template_process_unique');
        });

        Schema::create('planning_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_process_id')
                ->constrained('planning_template_processes')
                ->cascadeOnDelete();
            $table->foreignId('config_task_id')
                ->constrained('planning_config_tasks')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['template_process_id', 'config_task_id'], 'template_task_unique');
        });

        Schema::create('planning_template_phase_headings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_phase_id')
                ->constrained('planning_template_phases')
                ->cascadeOnDelete();
            $table->string('name'); 
            $table->enum('input_type', ['TEXT', 'NUMBER', 'DATETIME']);
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('key')->nullable(); // default headings have keys, null for custom
            $table->timestamps();

            $table->unique(['template_phase_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_templates');
        Schema::dropIfExists('planning_config_phases');
        Schema::dropIfExists('planning_config_processes');
        Schema::dropIfExists('planning_config_tasks');
    }
};
