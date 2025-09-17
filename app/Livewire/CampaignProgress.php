<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Services\CampaignService;
use Livewire\Component;

class CampaignProgress extends Component
{
    public Campaign $campaign;

    public array $stats = [];

    public function mount(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->updateStats();
    }

    /**
     * Écouter les events de broadcast pour mettre à jour en temps réel
     */
    protected function getListeners(): array
    {
        return [
            "echo-private:email-campaign.{$this->campaign->id},EmailSent" => 'updateStats',
            "echo-private:email-campaign.{$this->campaign->id},EmailFailed" => 'updateStats',
        ];
    }

    public function updateStats(?CampaignService $campaignService = null)
    {
        $this->campaign = $this->campaign->fresh();

        if (! $campaignService) {
            $campaignService = app(CampaignService::class);
        }

        $this->stats = $campaignService->getCampaignStats($this->campaign);
    }

    public function render()
    {
        return view('livewire.campaign-progress');
    }
}
