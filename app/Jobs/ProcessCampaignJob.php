<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private Campaign $campaign){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing Campaign job started', [
            'campaign_id' => $this->campaign->id,
            'total_emails' => $this->campaign->emails()->count(),
        ]);

        try {
            $campaign = $this->campaign;

            $jobs = [];

            foreach ($this->campaign->emails as $index => $email) {
                $delay = now()->addSeconds($index + 5);
                $jobs[] = (new SendSingleEmailJob($email))->delay($delay);
            }

            $batch = Bus::batch($jobs)
                ->then(function (Batch $batch) use ($campaign) {
                    $campaign->markAsCompleted();
                    Cache::forget('campaign_batch_' . $campaign->id);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($campaign) {
                    $campaign->markAsFailed();
                    Cache::forget('campaign_batch_' . $campaign->id);
                })
                ->finally(function (Batch $batch) use ($campaign) {
                    Cache::forget('campaign_batch_' . $campaign->id);
                })
                ->name("Campaign {$campaign->id}")
                ->dispatch();
            
            Cache::put('campaign_batch_' . $campaign->id, $batch->id, now()->addHours(24));

        } catch (\Throwable $th) {
            Log::error('Processing Campaign Job Failed', [
                'campaign_id' => $this->campaign->id,
                'error' => $th->getMessage(),
            ]);
            throw $th;
        }
    }
}
