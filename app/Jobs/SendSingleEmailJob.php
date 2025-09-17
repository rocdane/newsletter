<?php

namespace App\Jobs;

use App\Events\EmailFailed;
use App\Events\EmailSent;
use App\Mail\Letter;
use App\Models\Email;
use App\Models\EmailMeta;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSingleEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2; // Nombre de tentatives

    public int $backoff = 60; // 1 minute entre les tentatives

    public function __construct(
        private Email $email
    ) {}

    public function handle(): void
    {
        try {
            $this->email = $this->email->fresh(['suscriber']);

            Log::info('Processing email job', [
                'email_id' => $this->email->id,
                'subscriber_id' => $this->email->suscriber_id,
            ]);

            if (! $this->email->suscriber) {
                throw new Exception('Subscriber not found for email ID: '.$this->email->id);
            }

            if (! filter_var($this->email->suscriber->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid subscriber email: '.$this->email->suscriber->email);
            }

            Mail::to($this->email->suscriber->email)->send(new Letter($this->email));

            $this->email->markAsSent();

            EmailMeta::trackDelivered($this->email, [
                'sent_at' => now()->toISOString(),
            ]);

            $this->email->campaign->incrementSent();

            EmailSent::dispatch($this->email, 'Email sent successfully.');

        } catch (Exception $e) {
            $this->email->markAsFailed();

            $this->email->campaign->incrementFailed();

            EmailFailed::dispatch($this->email, $e->getMessage());

            throw $e;
        }
    }
}
