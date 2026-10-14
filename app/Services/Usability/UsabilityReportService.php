<?php

namespace App\Services\Usability;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class UsabilityReportService
{
    /**
     * @return array{
     *     participants:int,
     *     task_rows:int,
     *     success_rate:float,
     *     median_time_seconds:float,
     *     average_errors:float,
     *     average_confusion:float,
     *     by_role:array<string,array<string,int|float>>,
     *     by_task:array<string,array<string,int|float|string>>
     * }
     */
    public function analyzeTasks(string $path): array
    {
        $rows = $this->readCsv($path, [
            'participant_id', 'role', 'task_id', 'completed', 'time_seconds', 'errors', 'confusion_score',
        ]);

        if ($rows === []) {
            throw new InvalidArgumentException('CSV task usability tidak memiliki data.');
        }

        foreach ($rows as $index => &$row) {
            $line = $index + 2;
            $row['participant_id'] = trim((string) $row['participant_id']);
            $row['role'] = strtolower(trim((string) $row['role']));
            $row['task_id'] = trim((string) $row['task_id']);

            if ($row['participant_id'] === '' || $row['task_id'] === '') {
                throw new InvalidArgumentException("participant_id dan task_id wajib diisi pada baris {$line}.");
            }

            if (! in_array($row['role'], ['instructor', 'student'], true)) {
                throw new InvalidArgumentException("role harus instructor atau student pada baris {$line}.");
            }

            $row['completed'] = $this->binary($row['completed'], 'completed', $line);
            $row['time_seconds'] = $this->nonNegativeNumber($row['time_seconds'], 'time_seconds', $line);
            $row['errors'] = $this->nonNegativeNumber($row['errors'], 'errors', $line);
            $row['confusion_score'] = $this->rangeNumber($row['confusion_score'], 'confusion_score', 1, 5, $line);
        }
        unset($row);

        $participants = collect($rows)->pluck('participant_id')->unique()->count();
        $successRate = collect($rows)->avg('completed') * 100;
        $medianTime = $this->median(collect($rows)->pluck('time_seconds')->map(fn ($value): float => (float) $value)->all());

        $byRole = collect($rows)->groupBy('role')->map(function ($roleRows): array {
            return [
                'task_rows' => $roleRows->count(),
                'participants' => $roleRows->pluck('participant_id')->unique()->count(),
                'success_rate' => round($roleRows->avg('completed') * 100, 2),
                'median_time_seconds' => round($this->median($roleRows->pluck('time_seconds')->map(fn ($value): float => (float) $value)->all()), 2),
                'average_errors' => round((float) $roleRows->avg('errors'), 2),
                'average_confusion' => round((float) $roleRows->avg('confusion_score'), 2),
            ];
        })->all();

        $byTask = collect($rows)->groupBy('task_id')->map(function ($taskRows, string $taskId): array {
            $roles = $taskRows->pluck('role')->unique()->values();

            return [
                'task_id' => $taskId,
                'role' => $roles->count() === 1 ? (string) $roles->first() : 'mixed',
                'attempts' => $taskRows->count(),
                'success_rate' => round($taskRows->avg('completed') * 100, 2),
                'median_time_seconds' => round($this->median($taskRows->pluck('time_seconds')->map(fn ($value): float => (float) $value)->all()), 2),
                'average_errors' => round((float) $taskRows->avg('errors'), 2),
                'average_confusion' => round((float) $taskRows->avg('confusion_score'), 2),
            ];
        })->sortKeys()->all();

        return [
            'participants' => $participants,
            'task_rows' => count($rows),
            'success_rate' => round($successRate, 2),
            'median_time_seconds' => round($medianTime, 2),
            'average_errors' => round((float) collect($rows)->avg('errors'), 2),
            'average_confusion' => round((float) collect($rows)->avg('confusion_score'), 2),
            'by_role' => $byRole,
            'by_task' => $byTask,
        ];
    }

    /**
     * @return array{
     *     participants:int,
     *     average_sus:float,
     *     median_sus:float,
     *     by_role:array<string,array{participants:int,average_sus:float,median_sus:float}>,
     *     participant_scores:array<string,float>
     * }
     */
    public function analyzeSus(string $path): array
    {
        $required = ['participant_id', 'role'];
        foreach (range(1, 10) as $number) {
            $required[] = 'q'.$number;
        }

        $rows = $this->readCsv($path, $required);
        if ($rows === []) {
            throw new InvalidArgumentException('CSV SUS tidak memiliki data.');
        }

        $scores = [];
        $roles = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $participantId = trim((string) $row['participant_id']);
            $role = strtolower(trim((string) $row['role']));

            if ($participantId === '') {
                throw new InvalidArgumentException("participant_id wajib diisi pada baris {$line}.");
            }
            if (isset($scores[$participantId])) {
                throw new InvalidArgumentException("participant_id {$participantId} muncul lebih dari sekali pada CSV SUS.");
            }
            if (! in_array($role, ['instructor', 'student'], true)) {
                throw new InvalidArgumentException("role harus instructor atau student pada baris {$line}.");
            }

            $sum = 0.0;
            foreach (range(1, 10) as $number) {
                $answer = $this->rangeNumber($row['q'.$number], 'q'.$number, 1, 5, $line);
                $sum += $number % 2 === 1 ? $answer - 1 : 5 - $answer;
            }

            $scores[$participantId] = round($sum * 2.5, 2);
            $roles[$participantId] = $role;
        }

        $byRole = collect($scores)->groupBy(fn ($score, $participantId): string => $roles[$participantId])
            ->map(function ($roleScores): array {
                $values = $roleScores->values()->map(fn ($value): float => (float) $value)->all();

                return [
                    'participants' => count($values),
                    'average_sus' => round(array_sum($values) / count($values), 2),
                    'median_sus' => round($this->median($values), 2),
                ];
            })->all();

        $values = array_values($scores);

        return [
            'participants' => count($scores),
            'average_sus' => round(array_sum($values) / count($values), 2),
            'median_sus' => round($this->median($values), 2),
            'by_role' => $byRole,
            'participant_scores' => $scores,
        ];
    }

    /** @return list<array<string, string>> */
    private function readCsv(string $path, array $requiredHeaders): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File tidak dapat dibaca: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException("File tidak dapat dibuka: {$path}");
        }

        try {
            $headers = fgetcsv($handle);
            if (! is_array($headers)) {
                throw new InvalidArgumentException("CSV tidak memiliki header: {$path}");
            }

            $headers = array_map(fn ($header): string => strtolower(trim((string) $header)), $headers);
            $missing = array_values(array_diff($requiredHeaders, $headers));
            if ($missing !== []) {
                throw new InvalidArgumentException('Header CSV kurang: '.implode(', ', $missing));
            }

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || collect($values)->every(fn ($value): bool => trim((string) $value) === '')) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new InvalidArgumentException('Jumlah kolom CSV tidak konsisten.');
                }

                $rows[] = array_combine($headers, array_map(fn ($value): string => trim((string) $value), $values));
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function binary(mixed $value, string $field, int $line): int
    {
        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
            return 1;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
            return 0;
        }

        throw new InvalidArgumentException("{$field} harus 0/1 pada baris {$line}.");
    }

    private function nonNegativeNumber(mixed $value, string $field, int $line): float
    {
        if (! is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException("{$field} harus angka >= 0 pada baris {$line}.");
        }

        return (float) $value;
    }

    private function rangeNumber(mixed $value, string $field, float $min, float $max, int $line): float
    {
        if (! is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            throw new InvalidArgumentException("{$field} harus berada pada rentang {$min}-{$max} pada baris {$line}.");
        }

        return (float) $value;
    }

    /** @param list<float> $values */
    private function median(array $values): float
    {
        sort($values, SORT_NUMERIC);
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
