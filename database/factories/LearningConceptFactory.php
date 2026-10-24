<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\LearningConcept;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LearningConcept>
 */
class LearningConceptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'course_id' => Course::factory(),
            'learning_objective_id' => null,
            'code' => Str::upper('C-'.$this->faker->unique()->numerify('###')),
            'name' => Str::title($name),
            'description' => $this->faker->sentence(),
            'order' => 1,
            'is_active' => true,
        ];
    }
}
