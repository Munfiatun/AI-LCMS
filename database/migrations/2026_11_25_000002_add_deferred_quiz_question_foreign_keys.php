<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Older SQLite-compatible quiz migrations reference question tables
     * before those tables exist. MySQL/PostgreSQL require the referenced
     * tables to be created first. Add the deferred constraints once all
     * question tables have been created, preserving SQLite's original FKs.
     *
     * On already migrated databases, skip existing FKs for these columns.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->constraints() as [$table, $column, $referencedTable, $name, $onDelete]) {
            $existingKeys = Schema::getForeignKeys($table);
            $columnAlreadyConstrained = collect($existingKeys)->contains(
                fn (array $key): bool => in_array($column, $key['columns'] ?? [], true)
            );

            if ($columnAlreadyConstrained) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $name, $onDelete): void {
                $key = $blueprint->foreign($column, $name)->references('id')->on($referencedTable);

                if ($onDelete === 'set null') {
                    $key->nullOnDelete();
                } else {
                    $key->cascadeOnDelete();
                }
            });
        }
    }

    /**
     * Reverse only R5-named constraints. Never remove historical constraints
     * that were already present before this migration.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->constraints() as [$table, $column, $referencedTable, $name, $onDelete]) {
            $exists = collect(Schema::getForeignKeys($table))
                ->contains(fn (array $key): bool => ($key['name'] ?? null) === $name);

            if ($exists) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($name));
            }
        }
    }

    /** @return list<array{string, string, string, string, string}> */
    private function constraints(): array
    {
        return [
            ['quiz_questions', 'question_id', 'questions', 'r5_quiz_questions_question_fk', 'cascade'],
            ['quiz_attempt_questions', 'question_id', 'questions', 'r5_attempt_questions_question_fk', 'cascade'],
            ['quiz_attempt_options', 'option_id', 'question_options', 'r5_attempt_options_option_fk', 'cascade'],
            ['quiz_answers', 'question_id', 'questions', 'r5_quiz_answers_question_fk', 'cascade'],
            ['quiz_answers', 'selected_option_id', 'question_options', 'r5_quiz_answers_option_fk', 'set null'],
        ];
    }
};
