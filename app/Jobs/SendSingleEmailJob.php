<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Events\EmailFailed;
use App\Events\EmailSent;
use App\Mail\Letter;
use App\Models\Email;
use Exception;

class SendSingleEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(public Email $email) {}

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::info('Batch cancelled, skipping email', [
                'email_id' => $this->email->id,
                'batch_id' => $this->batch()?->id,
            ]);
            return;
        }

        try {
            Log::info('Processing email job', [
                'email_id' => $this->email->id,
                'subscriber_id' => $this->email->subscriber_id,
                'batch_id' => $this->batch()?->id,
            ]);

            $email = $this->email->fresh(['subscriber', 'campaign']);

            if (!$email->subscriber) {
                throw new Exception("Subscriber not found for email ID: {$email->id}");
            }

            if ($email->delivered_at) {
                Log::info('Email already delivered', ['email_id' => $email->id]);
                return;
            }

            Mail::to($email->subscriber->email)->send(new Letter($email));

            $email->markAsDelivered();

            EmailSent::dispatch($email, 'Email sent successfully.');

            Log::info('Email sent successfully', [
                'email_id' => $email->id,
                'subscriber_email' => $email->subscriber->email,
            ]);

        } catch (Exception $e) {
            Log::error('Email sending failed', [
                'email_id' => $this->email->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            EmailFailed::dispatch($this->email, $e->getMessage());
            
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('SendSingleEmailJob failed', [
            'email_id' => $this->email->id ?? null,
            'error' => $exception->getMessage(),
        ]);

        EmailFailed::dispatch($this->email, $exception->getMessage());
    }
}