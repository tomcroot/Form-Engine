<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('form_type')->default('data_entry'); // data_entry | file_upload
            $table->json('schema')->nullable();                  // field definitions for data_entry
            $table->string('template_file')->nullable();         // downloadable template for file_upload
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('draft');          // draft | published
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('name');                              // field key
            $table->string('label');                             // display label
            $table->string('type');                              // text, textarea, select, number, date, file, toggle, etc.
            $table->text('help_text')->nullable();               // help/instructions
            $table->json('options')->nullable();                 // for select/radio/checkbox
            $table->boolean('required')->default(false);
            $table->json('validation')->nullable();              // additional validation rules
            $table->unsignedInteger('order')->default(0);        // display order
            $table->timestamps();

            $table->index(['form_id', 'order']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->morphs('subject');                           // subject_type + subject_id (Activity, Project, Participation, etc.)
            $table->json('data')->nullable();                    // captured field values for data_entry
            $table->string('file_path')->nullable();             // uploaded file path for file_upload
            $table->foreignId('submitted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['form_id', 'subject_type', 'subject_id']);
            $table->index('submitted_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
