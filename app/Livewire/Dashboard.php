<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CampaignService;
use App\Services\EmailParsingService;

class Dashboard extends Component
{
    public $stats, $active_subscribers;

    public function mount()
    {
        $this->updateStats();
    }

    public function updateStats(? CampaignService $campaignService = null, ? EmailParsingService $emailParsingService = null)
    {
        if (!$campaignService) {
            $campaignService = app(CampaignService::class);
        }

        if (!$emailParsingService) {
            $emailParsingService = app(EmailParsingService::class);
        }

        $this->active_subscribers = $emailParsingService->getActiveSubscribers();

        $this->stats = $campaignService->getDashboardStats();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
