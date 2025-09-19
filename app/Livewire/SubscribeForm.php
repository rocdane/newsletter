<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subscriber;

class SubscribeForm extends Component
{
    public $email;

    protected $listeners = ['submit' => 'subscribe'];

    public function subscribe()
    {
        $this->validate([
            'email' => 'required|email|unique:emails,email'
        ]);

        Subscriber::firstOrCreate(['email' => $this->email]);

        session()->flash('message', 'You have successfully subscribed!');

        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.subscribe-form');
    }
}
