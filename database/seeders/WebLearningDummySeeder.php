<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Slidebook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WebLearningDummySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('WebLearningDummySeeder only runs in local/testing environments.');
            return;
        }

        $courses = Course::query()->published()
            ->whereHas('instructor.role', fn (Builder $query): Builder => $query->where('name', 'instructor'));
        $course = (clone $courses)->where('slug', 'pemograman-web')->first()
            ?? (clone $courses)->where('title', 'like', '%Web%')->orderBy('id')->first()
            ?? $courses->orderBy('id')->first();

        if (! $course) {
            throw new RuntimeException('Seeder membutuhkan kursus published milik guru yang sudah tersedia.');
        }

        /** @var list<array{title: string, description: string, questions: list<array{question: string, correct: string, distractors: list<string>, explanation: string}>}> $lessons */
        $lessons = json_decode(file_get_contents(__DIR__.'/web_learning_questions.json'), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($course, $lessons): void {
            Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            foreach ($lessons as $lesson) {
                $slug = 'dummy-'.Str::slug($lesson['title']);
                $marker = 'web-learning-dummy:'.$slug;
                $section = $course->sections()->firstOrCreate(['description' => $marker], [
                    'title' => 'Latihan: '.$lesson['title'],
                    'order' => ((int) $course->sections()->max('order')) + 1,
                    'status' => 'active',
                ]);
                $content = '<h2>'.e($lesson['title']).'</h2><p>'.e($lesson['description']).'</p>';
                foreach ($lesson['questions'] as $item) {
                    $content .= '<p>'.e($item['explanation']).'</p>';
                }
                $material = $section->materials()->firstOrCreate(['slug' => $slug], [
                    'title' => $lesson['title'], 'description' => $lesson['description'],
                    'content' => $content, 'duration_minutes' => 30, 'order' => 1,
                    'status' => 'published', 'published_at' => now(),
                ]);
                $slidebook = $material->slidebooks()->firstOrCreate(['description' => $marker], [
                    'title' => $lesson['title'], 'created_by' => $course->instructor_id,
                    'status' => Slidebook::STATUS_PUBLISHED, 'published_at' => now(), 'version' => 1,
                ]);
                foreach (array_chunk($lesson['questions'], 4) as $index => $items) {
                    $slidebook->slides()->firstOrCreate(['order' => $index + 1], [
                        'title' => $lesson['title'].' — Bagian '.($index + 1),
                        'content' => implode("\n\n", array_column($items, 'explanation')),
                        'status' => 'approved', 'needs_review' => false,
                    ]);
                }
                $bank = QuestionBank::firstOrCreate([
                    'instructor_id' => $course->instructor_id, 'course_id' => $course->id, 'description' => $marker,
                ], ['title' => 'Dummy: '.$lesson['title'], 'status' => QuestionBank::STATUS_ACTIVE]);
                $quiz = $course->quizzes()->firstOrCreate(['section_id' => $section->id, 'description' => $marker], [
                    'title' => 'Latihan: '.$lesson['title'], 'total_questions' => 20,
                    'instructions' => 'Pilih satu jawaban benar pada setiap soal.',
                    'passing_score' => 70, 'duration_minutes' => 30, 'max_attempts' => 3,
                    'status' => 'draft',
                ]);
                foreach ($lesson['questions'] as $index => $item) {
                    $question = $bank->questions()->firstOrCreate(['order' => $index + 1], [
                        'question_text' => $item['question'], 'type' => Question::TYPE_MULTIPLE_CHOICE,
                        'topic' => $lesson['title'], 'difficulty' => Question::DIFFICULTY_MEDIUM,
                        'explanation' => $item['explanation'], 'points' => 5,
                        'needs_review' => false, 'answer_source' => Question::SOURCE_MANUAL,
                        'status' => Question::STATUS_APPROVED,
                    ]);
                    if ($question->wasRecentlyCreated) {
                        $options = $item['distractors'];
                        array_splice($options, $index % 4, 0, [$item['correct']]);
                        foreach ($options as $optionIndex => $text) {
                            $question->options()->create([
                                'option_text' => $text, 'is_correct' => $optionIndex === $index % 4, 'order' => $optionIndex + 1,
                            ]);
                        }
                    }
                    $quiz->quizQuestions()->firstOrCreate(['question_id' => $question->id], ['points' => 5, 'order' => $index + 1]);
                }
            }
        });

        $this->command?->info("Data dummy siap pada kursus #{$course->id} ({$course->title}): 5 materi, 5 quiz draft, 100 soal. Data yang sudah ada dipertahankan.");
    }
}
