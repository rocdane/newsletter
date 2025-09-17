@extends('template.mail')

@section('title',__('mail.title'))

@section('content')
<div class="container">
    <div class="header">
        <h1>{{$subject}}</h1>
    </div>
    <div class="content">
        <p>{{__('mail.greet',['name'=>$name])}},</p>
        <p>{{$content}}</p>
        <p>{{__('mail.thanks')}}</p>
    </div>
    <a href={{$target}} class="cta">{{__('mail.action')}}</a>
    <div class="footer">
        <p>&copy; 2024 {{env('APP_NAME')}}. {{__('mail.right')}}.</p>
        <p>{{__('mail.unsubscribe-message')}}, <a href={{$link}}>{{__('mail.unsubscribe-cta')}}</a>.</p>
        <img src={{$tracker}} alt="" style="display:none;">
    </div>
</div>
@stop