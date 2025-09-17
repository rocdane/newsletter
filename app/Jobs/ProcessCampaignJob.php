<?php

namespace App\Jobs;

use App\Events\CampaignStarted;
use App\Models\Campaign;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300; // 5 minutes max

    public function __construct(
        private Campaign $campaign
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Starting campaign processing', [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
            ]);

            // 1. Vérifier que la campagne existe toujours
            $this->campaign = $this->campaign->fresh();
            if (! $this->campaign) {
                throw new Exception('Campaign not found');
            }

            // 2. Vérifier le statut (éviter double traitement)
            if ($this->campaign->status !== 'pending') {
                Log::warning('Campaign already processed or processing', [
                    'campaign_id' => $this->campaign->id,
                    'status' => $this->campaign->status,
                ]);

                return;
            }

            // 3. Mettre à jour le statut
            $this->campaign->update(['status' => 'processing']);

            // 4. Récupérer les emails avec validation
            $emails = $this->campaign->emails()
                ->pending()
                ->with('subscriber')
                ->get();

            Log::info('Emails retrieved for processing', [
                'campaign_id' => $this->campaign->id,
                'total_emails' => $emails->count(),
                'email_ids' => $emails->pluck('id')->toArray(),
            ]);

            // 5. Vérifier qu'il y a des emails à traiter
            if ($emails->isEmpty()) {
                Log::warning('No emails to process for campaign', [
                    'campaign_id' => $this->campaign->id,
                ]);

                $this->campaign->update(['status' => 'completed']);

                return;
            }

            // 6. MAINTENANT on peut dispatcher l'event de début
            CampaignStarted::dispatch($this->campaign);

            // 7. Dispatcher les jobs d'envoi individuels
            $dispatchedJobs = 0;
            foreach ($emails as $email) {
                // Validation supplémentaire avant dispatch
                if ($email->subscriber) {
                    SendSingleEmailJob::dispatch($email);
                    $dispatchedJobs++;
                } else {
                    Log::warning('Skipping email with inactive subscriber', [
                        'email_id' => $email->id,
                        'subscriber_id' => $email->subscriber_id,
                    ]);

                    // Marquer l'email comme failed
                    $email->markAsFailed();
                    $this->campaign->incrementFailed();
                }
            }

            Log::info('Email jobs dispatched successfully', [
                'campaign_id' => $this->campaign->id,
                'jobs_dispatched' => $dispatchedJobs,
                'jobs_skipped' => $emails->count() - $dispatchedJobs,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process email campaign', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Marquer la campagne comme failed
            $this->campaign->update(['status' => 'failed']);

            // Re-lancer l'exception pour que Laravel gère le retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('ProcessCampaignJob failed permanently', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);

        // Marquer la campagne comme failed si pas déjà fait
        if ($this->campaign->status !== 'failed') {
            $this->campaign->update(['status' => 'failed']);
        }
    }
}
