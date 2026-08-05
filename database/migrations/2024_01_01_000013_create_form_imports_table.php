<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_path', 500);
            $table->enum('file_type', ['docx', 'xlsx']);
            $table->enum('status', ['pending', 'processing', 'preview_ready', 'completed', 'failed'])->default('pending');
            $table->json('preview_schema')->nullable();
            $table->json('mapping')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_imports');
    }
};
