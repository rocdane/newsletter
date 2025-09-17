@php
    $type ??= 'text';
    $class ??= null;
    $label ??= null;
    $name ??= '';
@endphp

<div @class(['form-group', $class])>
    <label for={{$name}}>{{$label}}</label>
    @if($type === 'textarea')
    <textarea wire:model={{$name}} class="form-control @error($name) is-invalid @enderror" cols="30" rows="20"></textarea>
    @else
    <input wire:model={{$name}} type={{$type}} class="form-control @error($name) is-invalid @enderror">
    @endif
    
    @error($name) <div class="invalid-feedback">{{$message}}</div> @enderror
</div>