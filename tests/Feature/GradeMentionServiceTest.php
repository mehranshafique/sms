<?php

namespace Tests\Feature;

use App\Services\GradeMentionService;
use Tests\TestCase;

class GradeMentionServiceTest extends TestCase
{
    public function test_mention_from_percentage_excellent(): void
    {
        $service = app(GradeMentionService::class);
        $mention = $service->fromPercentage(85);
        $this->assertSame(__('lmd.mention_excellent'), $mention);
    }

    public function test_mention_from_percentage_fail(): void
    {
        $service = app(GradeMentionService::class);
        $mention = $service->fromPercentage(40);
        $this->assertSame(__('lmd.mention_fail'), $mention);
    }

    public function test_mention_from_average_on_twenty_scale(): void
    {
        $service = app(GradeMentionService::class);
        $this->assertSame(__('lmd.mention_good'), $service->fromAverage(15));
    }
}
