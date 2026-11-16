<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_events', function (Blueprint $table): void {
            // NULL remains valid for repeatable events and legacy rows.
            // recordOnce() alone populates this key; the UNIQUE index handles
            // concurrent first-writes without rewriting historical snapshots.
            $table->string('deduplication_key', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('research_events', function (Blueprint $table): void {
            $table->dropUnique(['deduplication_key']);
            $table->dropColumn('deduplication_key');
        });
    }
};
