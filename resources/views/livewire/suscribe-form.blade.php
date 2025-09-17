<x-layouts.app>
    <x-slot name="title">Suscribe</x-slot>

    <div class="flex items-center justify-center min-h-screen">
        <form wire:submit.prevent="suscribe" class="form-horizontal">

        @include('shared.input',['label'=>'Email address', 'name'=>'email'])

        <button class="btn btn-outline-primary btn-rounded" type="submit">
            Suscribe
        </button>
    </form>
    </div>
</x-layouts.app>
