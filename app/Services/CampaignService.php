<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Repositories\EmailRepository;
use App\Jobs\ProcessCampaignJob;

class CampaignService
{
    public function __construct(private EmailRepository $emailRepository) {}

    public function createCampaign(
        Collection $subscribers,
        string $subject,
        string $content,
        ?string $campaignName = null,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): Campaign {

        $campaign = Campaign::create([
            'name' => $campaignName ?: 'Campagne du '.now()->format('d/m/Y H:i'),
            'subject' => $subject,
            'content' => $content,
            'from_name' => $fromName,
            'from_email' => $fromEmail
        ]);

        $this->emailRepository->createBulkEmails($subscribers, $campaign);

        $campaign->refresh();

        ProcessCampaignJob::dispatch($campaign);

        return $campaign;
    }

    public function getDashboardStats(): array
    {
        return [
            'total_campaigns' => Campaign::count(),
            'total_pending' => $this->emailRepository->getPendingEmails()->count(),
            'total_delivered' => $this->emailRepository->getDeliveredEmails()->count(),
            'total_opened' => $this->emailRepository->getOpenedEmails()->count(),
            'total_clicked' => $this->emailRepository->getclickedEmails()->count(),
        ];
    }
}
