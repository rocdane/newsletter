<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Services\CampaignService;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;

class CampaignProgress extends Component
{
    public Campaign $campaign;
    public $batchId;
    public $processed = 0;
    public $pending = 0;
    public $failed = 0;
    public $total = 0;
    public $progress = 0;
    public $finished = true;
    public $status = 'idle';

    protected $listeners = ['refreshProgress' => '$refresh'];

    public function mount(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->batchId = Cache::get('campaign_batch_'.$campaign->id);
        
        $this->pollProgress();
    }

    public function pollProgress()
    {
        $this->batchId = Cache::get('campaign_batch_'.$this->campaign->id);

        if (!$this->batchId) {
            $this->status = 'completed';
            return;
        }

        $batch = Bus::findBatch($this->batchId);

        if (!$batch) {
            $this->status = 'completed';
            Cache::forget('campaign_batch_' . $this->campaign->id);
            return;
        }

        $this->processed = $batch->processedJobs();
        $this->pending = $batch->pendingJobs;
        $this->failed = $batch->failedJobs;
        $this->total = $batch->totalJobs;
        $this->progress = $batch->progress();
        $this->finished = $batch->finished();    
        
        if ($batch->cancelled()) {
            $this->status = 'cancelled';
        } elseif ($batch->finished()) {
            $this->status = 'completed';
            $this->campaign->markAsCompleted();
            Cache::forget('campaign_batch_' . $this->campaign->id);
        } else {
            $this->status = 'sending';
        }

        $this->dispatch('refreshProgress');
    }

    public function cancelCampaign()
    {
        if ($this->batchId) {
            $batch = Bus::findBatch($this->batchId);
            
            if ($batch && !$batch->finished()) {
                $batch->cancel();
                $this->status = 'cancelled';
            }

            $this->campaign->delete();
        }

        return redirect()->route('email.campaign.create');
    }

    public function getStatsProperty()
    {
        $sent = $this->campaign->emails()->count();
        $opened = $this->campaign->emails()->opened()->count();
        $clicked = $this->campaign->emails()->clicked()->count();
        $delivered = $this->campaign->emails()->delivered()->count();

        return [
            'sent' => $sent,
            'delivered_count' => $delivered,
            'opened_count' => $opened,
            'clicked_count' => $clicked,
            'pending_count' => $this->campaign->emails()->pending()->count(),
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0,
            'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 1) : 0,
            'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0,
        ];
    }

    public function render()
    {
        $this->pollProgress();

        return view('livewire.campaign-progress');
    }
}
