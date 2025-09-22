<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Services\CampaignService;
use App\Services\EmailParsingService;

use Illuminate\Support\Facades\Session;

class CampaignForm extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 3;

    public $campaignId;
    public $campaignName;
    public $subject;
    public $content;
    public $fromName;
    public $fromEmail;
    public $file;

    protected $rules = [
        // Step 1
        'campaignName' => 'required|min:3|max:255',
        'subject' => 'required|min:3|max:255',
        'content' => 'required|min:10',

        // Step 2
        'fromName' => 'required|min:2|max:100',
        'fromEmail' => 'required|email',
        'file' => 'nullable|file|mimes:csv,txt|max:2048',
    ];

    protected $messages = [
        'campaignName.required' => 'Le nom de campagne est obligatoire.',
        'subject.required' => 'Le sujet de l\'email est obligatoire.',
        'content.required' => 'Le contenu de l\'email ne peut pas être vide.',
    ];

    public function mount()
    {
        $this->fromEmail = auth()->user()->email ?? config('mail.from.address');
        $this->fromName = auth()->user()->name ?? config('mail.from.name');
    }
    
    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            for ($i = 1; $i < $step; $i++) {
                $this->validateStep($i);
            }
            $this->currentStep = $step;
        }
    }
    
    private function validateCurrentStep()
    {
        $this->validateStep($this->currentStep);
    }
    
    private function validateStep($step)
    {
        switch ($step) {
            case 1:
                $this->validate([
                    'campaignName' => $this->rules['campaignName'],
                    'subject' => $this->rules['subject'],
                    'content' => $this->rules['content'],
                ]);
                
                break;
                
            case 2:
                $this->validate([
                    'fromName' => $this->rules['fromName'],
                    'fromEmail' => $this->rules['fromEmail'],
                    'file' => $this->rules['file'],
                ]);
                break;
        }
    }

    public function createCampaign(CampaignService $campaignService, EmailParsingService $emailParsingService)
    {
        for ($i = 1; $i <= $this->totalSteps; $i++) {
            $this->validateStep($i);
        }
        
        try {
            $subscribers = $emailParsingService->createSubscribers($this->file);

            $campaign = $campaignService->createCampaign(
                $subscribers, 
                $this->subject, 
                $this->content, 
                $this->campaignName,
                $this->fromName,
                $this->fromEmail);
            
            $this->dispatch('send-campaign', ['campaignId' => $campaign->id]);
            
            session()->flash('success', 'Campagne créée avec succès !');

            $this->redirect('/campaigns');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }
    
    public function getProgressPercentage()
    {
        return ($this->currentStep / $this->totalSteps) * 100;
    }
    
    public function isStepComplete($step)
    {
        try {
            $this->validateStep($step);
            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    public function render()
    {
        return view('livewire.campaign-form');
    }
}
