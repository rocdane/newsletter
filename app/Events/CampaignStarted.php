<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Campaign $campaign
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
