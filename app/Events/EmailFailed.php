<?php

namespace App\Events;

use App;
use App\Models\Email;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmailFailed implements ShouldBroadcast
{

    use ObjectLogger, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct( public Email $email, public string $errorMessage) 
    {
        //Instance Initialization
        $this->logCreation("EMAIL FAILED EVENT");
    }

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
            'failed_emails' => $campaign->failed_emails,
            'total_emails' => $campaign->total_emails,
            'progress_percentage' => $campaign->progress_percentage,
            'error' => $this->errorMessage,
        ];
    }
}
