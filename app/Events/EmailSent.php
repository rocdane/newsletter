<?php

namespace App\Events;

use App\Models\Email;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Email $email,
        public string $successMessage
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('email-campaign.'.$this->email->campaign_id),
        ];
    }

    public function broadcastWith(): array
    {
        $campaign = $this->email->campaign;

        return [
            'email_id' => $this->email->id,
            'campaign_id' => $campaign->id,
            'sent_emails' => $campaign->sent_emails,
            'total_emails' => $campaign->total_emails,
            'progress_percentage' => $campaign->progress_percentage,
            'success' => $this->successMessage,
        ];
    }
}
