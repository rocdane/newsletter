<?php

namespace App\Livewire;

use App\Models\Campaign;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;

class CampaignProgress extends Component
{
    public Campaign $campaign;
    public $batchId;
    public $progress = 0;
    public $processed = 0;
    public $pending = 0;
    public $failed = 0;
    public $total = 0;
    public $status = 'idle';
    public $polling = true;
    
    protected $listeners = ['refreshProgress' => 'pollProgress'];

    public function mount(Campaign $campaign)
    {
        $this->campaign = $campaign->refresh();
        $this->batchId = Cache::get('campaign_batch_'.$campaign->id);
        $this->pollProgress();
        if ($this->campaign->hasActiveBatch()) {
            $this->startPolling();
        }
    }

    public function pollProgress()
    {
        
        if (!$this->polling) {
            return;
        }

        $this->batchId = $this->campaign->getMetadata('batch_id') 
            ?: Cache::get('campaign_batch_'.$this->campaign->id);

        if (!$this->batchId) {
            $this->status = 'completed';
            return;
        }

        $batch = Bus::findBatch($this->batchId);

        if (!$batch->id) {
            $this->status = 'completed';
            Cache::forget('campaign_batch_' . $this->campaign->id);
            $this->campaign->removeMetadata('batch_id');
            $this->stopPolling();
            return;
        }

        $this->campaign->updateBatchProgress($this->batchId);

        $batchStats = $this->campaign->getBatchStats();

        $this->processed = $batchStats['processed'][0];
        $this->pending = $batchStats['pending'][0];
        $this->failed = $batchStats['failed'][0];
        $this->total = $batchStats['total'][0];
        $this->progress = round(($this->processed + $this->failed + $this->pending) / $this->total * 100, 1) ;

        if ($batch->cancelled()) {
            $this->status = 'cancelled';
            $this->stopPolling();
        } elseif ($batch->finished()) {
            $this->status = 'completed';
            $this->campaign->markAsCompleted();
            $this->stopPolling();
        } else {
            $this->status = 'processing';
        }

        $this->dispatch('progress-updated', [
            'progress' => $this->progress,
            'processed' => $this->processed,
            'total' => $this->total
        ]);
    }

    public function startPolling()
    {
        $this->polling = true;
        $this->dispatch('start-polling');
    }

    public function stopPolling()
    {
        $this->polling = false;
        $this->dispatch('stop-polling');
    }

    public function cancelCampaign()
    {
        $batchId = $this->campaign->batch_id;
        
        if ($batchId) {
            $batch = Bus::findBatch($batchId);
            
            if ($batch && !$batch->finished()) {
                $batch->cancel();
                $this->status = 'cancelled';
                $this->campaign->updateBatchProgress($batchId);
            }
            
            $this->campaign->clearBatchId();
        }

        $this->stopPolling();

        return redirect()->route('email.campaign.create');
    }

    public function render()
    {
        return view('livewire.campaign-progress', [
            'stats' => $this->campaign->getEmailStats(),
        ]);
    }
}