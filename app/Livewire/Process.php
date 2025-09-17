<?php

namespace App\Livewire;

use Livewire\Wireable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;

class Process implements Wireable
{
    public $processed = 0;
    public $pending = 0;
    public $failed = 0;
    public $total = 0;
    public $progress = 0;
    public $finished = true;

    public function __construct()
    {
        $batch_id = Cache::get('mailing_batch_id', 0);
        $batch = Bus::findBatch($batch_id);
        if(!is_null($batch)){
            $this->processed = $batch->processedJobs();
            $this->pending = $batch->pendingJobs;
            $this->failed = $batch->failedJobs;
            $this->total = $batch->totalJobs;
            $this->progress = $batch->progress();
            $this->finished = $batch->finished();    
        }
    }

    public function toLivewire()
    {
        return [
            'processed' => $this->processed,
            'pending' => $this->pending,
            'failed' => $this->failed,
            'total' => $this->total,
            'progress' => $this->progress,
            'finished' => $this->finished,
        ];
    }
    
    public static function fromLivewire($batch)
    {
        return new static($batch);
    }
}