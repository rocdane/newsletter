<?php

namespace App\Events;

use App\Models\EmailCampaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailCampaignStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EmailCampaign $campaign
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('email-campaign.'.$this->campaign->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'status' => $this->campaign->status,
            'total_emails' => $this->campaign->total_emails,
        ];
    }
}
