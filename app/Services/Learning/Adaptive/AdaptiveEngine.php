<?php

namespace App\Services\Learning\Adaptive;

use App\Models\LearningConcept;
use App\Models\User;

interface AdaptiveEngine
{
    public function decide(User $student, LearningConcept $concept): AdaptiveDecision;
}
