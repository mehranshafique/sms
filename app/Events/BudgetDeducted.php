<?php

namespace App\Events;

use App\Models\FundRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetDeducted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fundRequest;

    /**
     * Create a new event instance.
     *
     * @param FundRequest $fundRequest
     * @return void
     */
    public function __construct(FundRequest $fundRequest)
    {
        $this->fundRequest = $fundRequest;
    }
}