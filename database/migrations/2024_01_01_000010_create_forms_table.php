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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('slug', 100)->unique();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('schema'); // single source of truth
            $table->json('settings')->nullable(); // submit msg, redirect, etc.
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('accepts_submissions')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for scale
            $table->index(['user_id', 'status']);
            $table->index(['slug']);
            $table->index(['status', 'accepts_submissions']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
