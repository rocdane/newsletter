<?php

namespace App\Livewire;

use Livewire\Component;

class SingleJobProgress extends Component
{
    public Process $process;

    public function mount()
    {
        $this->process = new Process();
    }

    public function render()
    {
        return view('livewire.single-job-progress');
    }
}
