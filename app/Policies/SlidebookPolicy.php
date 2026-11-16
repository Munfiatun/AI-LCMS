<?php

namespace App\Policies;

use App\Models\LearningMaterial;
use App\Models\Slidebook;
use App\Models\User;

class SlidebookPolicy
{
    /**
     * Determine whether the user can view the slidebook.
     */
    public function view(?User $user, Slidebook $slidebook): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $material = $slidebook->material;
        $course = $material?->section?->course;
        if (! $course) {
            return false;
        }

        if ($user->isInstructor()) {
            return $user->id === $course->instructor_id;
        }

        return $user->isStudent()
            && $slidebook->isPublished()
            && $material->status === LearningMaterial::STATUS_PUBLISHED
            && $material->section->status === 'active'
            && $course->isPublished()
            && $course->enrollments()->where('student_id', $user->id)->whereIn('status', ['active', 'completed'])->exists();
    }

    /**
     * Determine whether the user can generate slidebook for a material.
     */
    public function generate(User $user, LearningMaterial $material): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $user->id === $material->section?->course?->instructor_id);
    }

    /**
     * Determine whether the user can update the slidebook.
     */
    public function update(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $user->id === $slidebook->material?->section?->course?->instructor_id);
    }

    /**
     * Determine whether the user can approve the slidebook.
     */
    public function approve(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $user->id === $slidebook->material?->section?->course?->instructor_id);
    }

    /**
     * Determine whether the user can publish the slidebook.
     */
    public function publish(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $user->id === $slidebook->material?->section?->course?->instructor_id);
    }

    /**
     * Determine whether the user can delete the slidebook.
     */
    public function delete(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $user->id === $slidebook->material?->section?->course?->instructor_id);
    }
}
