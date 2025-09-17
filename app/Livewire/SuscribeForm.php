<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Suscriber;

class SuscribeForm extends Component
{
    public $email;

    protected $listeners = ['submit' => 'suscribe'];

    public function suscribe()
    {
        $this->validate([
            'email' => 'required|email|unique:emails,email'
        ]);

        Suscriber::create(['email' => $this->email]);

        session()->flash('message', 'You have successfully subscribed!');

        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.suscribe-form');
    }
}
