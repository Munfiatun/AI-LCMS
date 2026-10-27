<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adaptive_algorithm_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('engine_type', 64);
            $table->string('version', 32);
            $table->string('status', 32)->default('experimental');
            $table->boolean('is_baseline')->default(false);
            $table->text('description')->nullable();
            $table->json('configuration');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['engine_type', 'status']);
            $table->index(['is_baseline', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptive_algorithm_versions');
    }
};
