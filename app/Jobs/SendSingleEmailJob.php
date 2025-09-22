<?php

namespace App\Jobs;

use App\Events\EmailFailed;
use App\Events\EmailSent;
use App\Mail\Letter;
use App\Models\Email;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSingleEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $backoff = 60;

    public function __construct(
        private Email $email
    ) {}

    public function handle(): void
    {
        $email = $this->email->fresh(['subscriber']);

        if($this->batch() && $this->batch()->cancelled())
        {
            return ;
        }

        try {
            Log::info('Processing email job', [
                'email_id' => $email->id,
                'subscriber_id' => $email->subscriber_id,
            ]);

            if (! $email->subscriber) {
                throw new Exception("Subscriber not found for email ID: {$email->id}");
            }

            Mail::to($email->subscriber->email)->send(new Letter($email));

            $email->markAsDelivered();

            EmailSent::dispatch($email, 'Email sent successfully.');

        } catch (Exception $e) {
            EmailFailed::dispatch($email, $e->getMessage());

            Log::error('Email sending failed', [
                'email_id' => $email->id ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
