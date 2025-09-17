@extends('template.admin')

@section('title', 'Dashboard')

@section('content')
    @if(!is_null($dashboard))
    <div class="content-wrapper">
        <div class="row">
            @include('shared.indicator',['title'=>'Sent','value'=>$dashboard->sent,'icon'=>'ti-email','class'=>'success'])
            @include('shared.indicator',['title'=>'Opened','value'=>$dashboard->opened,'icon'=>'ti-eye','class'=>'warning'])
            @include('shared.indicator',['title'=>'Clicks','value'=>$dashboard->clicks,'icon'=>'ti-target','class'=>'primary'])
            @include('shared.indicator',['title'=>'Unsubscribed','value'=>$dashboard->unsubscribed,'icon'=>'ti-na','class'=>'danger'])
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card position-relative">
                    <div class="card-body">
                        <p class="card-title">Mailing Reports</p>
                        <div class="row">
                            <div class="col-md-12 col-xl-3 d-flex flex-column justify-content-center">
                                <div class="ml-xl-4">
                                    <h1>{{$dashboard->emails}}</h1>
                                    <h3 class="font-weight-light mb-xl-4">Mailing</h3>
                                    <p class="text-muted mb-2 mb-xl-0">The total number of email sent.</p>
                                </div>  
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@stop