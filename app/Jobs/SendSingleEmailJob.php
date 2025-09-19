<?php

namespace App\Jobs;

use App\Events\EmailFailed;
use App\Events\EmailSent;
use App\Mail\Letter;
use App\Models\Email;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSingleEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $backoff = 60;

    public function __construct(
        private Email $email
    ) {}

    public function handle(): void
    {
        try {
            $this->email = $this->email->fresh(['subscriber']);

            Log::info('Processing email job', [
                'email_id' => $this->email->id,
                'subscriber_id' => $this->email->subscriber_id,
            ]);

            if (! $this->email->subscriber) {
                throw new Exception("Subscriber not found for email ID: {$this->email->id}");
            }

            if (! filter_var($this->email->subscriber->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid subscriber email: {$this->email->subscriber->email}");
            }

            Mail::to($this->email->subscriber->email)->send(new Letter($this->email));

            $this->email->markAsDelivered();

            EmailSent::dispatch($this->email, 'Email sent successfully.');

        } catch (Exception $e) {
            EmailFailed::dispatch($this->email, $e->getMessage());

            Log::error('Email sending failed', [
                'email_id' => $this->email->id ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
