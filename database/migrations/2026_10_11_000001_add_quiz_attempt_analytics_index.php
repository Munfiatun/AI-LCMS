<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->index(
                ['quiz_id', 'student_id', 'status', 'submitted_at'],
                'quiz_attempts_latest_submitted_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropIndex('quiz_attempts_latest_submitted_index');
        });
    }
};
