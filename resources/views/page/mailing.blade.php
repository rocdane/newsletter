@extends('template.admin')

@section('title', 'Mailing')

@section('content')
    <div class="content-wrapper">
        <div class="row">
            @livewire('single-job-progress')
        </div>
        <div class="row">
            @livewire('mailing-form')
        </div>
    </div>
@stop