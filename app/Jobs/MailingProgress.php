<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\NewsLetter;
use App\Models\Email;
use App\Models\Tracker;
use App\Services\MailService;
use Throwable;

class MailingProgress implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $email)
    {}

    /**
     * Execute the job.
     */
    public function handle(MailService $mailService): void
    {
        try {
            if($mailService->send($this->email)){
                $email = Email::firstOrCreate(
                    ['address' => $this->email['address']],
                    $this->email
                );
    
                if(!is_null($email)){
                    Tracker::firstOrCreate(
                        ['email_id' => $email->id],
                        ['email_id' => $email->id,
                        'sent' => true,
                        'opened' => false,
                        'clicks' => false,
                        'unsubscribed' => false
                    ]);
                }
            }
            
            \Log::info('Mail sent successfully to ' . $email->address);
        } catch (Throwable $th) {
            \Log::error('Failed to send email : ' . $th->getMessage());
        }
    }
}
