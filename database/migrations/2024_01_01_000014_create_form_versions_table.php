<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Part D: Form versioning & rollback
        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('label', 100)->nullable();
            $table->json('schema');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'version']);
            $table->index(['form_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_versions');
    }
};
