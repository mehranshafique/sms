<?php

namespace App\Services;

/**
 * Congolese / LMD grading mentions on /20 scale.
 */
class GradeMentionService
{
    public function fromAverage(float $average, bool $onTwentyScale = true): string
    {
        $score = $onTwentyScale ? $average : ($average / 100) * 20;

        return match (true) {
            $score >= 16 => __('lmd.mention_excellent'),
            $score >= 14 => __('lmd.mention_good'),
            $score >= 12 => __('lmd.mention_fair'),
            $score >= 10 => __('lmd.mention_pass'),
            default => __('lmd.mention_fail'),
        };
    }

    public function fromPercentage(float $percentage): string
    {
        return $this->fromAverage(($percentage / 100) * 20, true);
    }
}
