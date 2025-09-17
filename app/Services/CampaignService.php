<?php

namespace App\Services;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Repositories\EmailRepository;
use App\Repositories\SubscriberRepository;
use Illuminate\Http\UploadedFile;

class CampaignService
{
    public function __construct(
        private EmailParsingService $emailParsingService,
        private SubscriberRepository $subscriberRepository,
        private EmailRepository $emailRepository
    ) {}

    public function createCampaign(
        UploadedFile $file,
        string $subject,
        string $content,
        ?string $campaignName = null
    ): Campaign {

        // todo: validate file type and size before parsing
        $emails = $this->emailParsingService->parseEmailFile($file);

        if (empty($emails)) {
            throw new \InvalidArgumentException('Aucun email valide trouvé dans le fichier.');
        }

        $subscribers = $this->subscriberRepository->bulkCreateFromEmails($emails);

        // create the campaign
        $campaign = Campaign::create([
            'name' => $campaignName ?: 'Campagne du '.now()->format('d/m/Y H:i'),
            'subject' => $subject,
            'content' => $content,
            'total_emails' => count($emails),
            'status' => 'pending',
        ]);

        $this->emailRepository->createBulkEmails($subscribers, $subject, $content, $campaign);

        $campaign->refresh();

        // todo: dispatch job to process campaign
        ProcessCampaignJob::dispatch($campaign);

        return $campaign;
    }

    public function getCampaignStats(Campaign $campaign): array
    {
        $emails = $campaign->emails()->get();

        return [
            'total' => $campaign->total_emails,
            'sent' => $campaign->sent_emails,
            'failed' => $campaign->failed_emails,
            'pending' => $campaign->total_emails - $campaign->sent_emails - $campaign->failed_emails,
            'progress_percentage' => $campaign->progress_percentage,
            'opened_count' => $emails->sum(function ($email) {
                return $email->metas()->where('type', 'opened')->count();
            }),
            'clicked_count' => $emails->sum(function ($email) {
                return $email->metas()->where('type', 'clicked')->count();
            }),
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'total_campaigns' => Campaign::count(),
            'total_sent' => $this->emailRepository->getSentEmails()->count(),
            'active_subscribers' => $this->subscriberRepository->getActiveSubscribers()->count(),
        ];
    }
}
