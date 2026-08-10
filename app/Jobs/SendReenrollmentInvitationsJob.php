<?php

namespace App\Jobs;

use App\Models\ReenrollmentCampaign;
use App\Services\ReenrollmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendReenrollmentInvitationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(
        public int $campaignId,
        public bool $isReminder = false
    ) {
    }

    public function handle(ReenrollmentService $service): void
    {
        $campaign = ReenrollmentCampaign::find($this->campaignId);
        if (! $campaign) {
            return;
        }

        try {
            $stats = $service->sendInvitations($campaign, $this->isReminder);

            Log::info('Re-enrollment invitations processed', [
                'campaign_id' => $campaign->id,
                'reminder' => $this->isReminder,
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            Log::error('Re-enrollment invitations failed: ' . $e->getMessage(), [
                'campaign_id' => $campaign->id,
                'reminder' => $this->isReminder,
            ]);
            throw $e;
        }
    }
}
