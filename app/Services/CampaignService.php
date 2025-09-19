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

    public function getCampaignStats(Campaign $campaign): array
    {
        $total = $campaign->emails()->count();
        $delivered = $campaign->emails()->delivered()->count();
        $pending = $campaign->emails()->pending()->count();
        $clicked = $campaign->emails()->clicked()->count();

        if (($delivered + $pending) >= $total) {
            $campaign->update(['status' => CampaignStatus::COMPLETED->value]);
        }

        return [
            'total' => $total,
            'delivered' => $delivered,
            'pending' => $pending,
            'clicked' => $clicked,
            'progress_percentage' => round(($sent + $pending) / $total * 100, 2),
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'total_campaigns' => Campaign::count(),
            'total_delivered' => $this->emailRepository->getDeliveredEmails()->count(),
        ];
    }
}
