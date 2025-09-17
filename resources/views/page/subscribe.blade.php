@extends('template.public')

@section('title', 'Suscription')

@section('content')
    <div class="content-wrapper">
        <div class="row">
            @livewire('suscribe-form')
        </div>
    </div>
@stop