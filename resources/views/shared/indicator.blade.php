@php
$title ??= 'null';
$value ??= 0;
$icon ??= 'null';
$class ??= 'dark';
@endphp
<div class="col-md-3 grid-margin stretch-card">
    <div class="card text-{{$class}}">
        <div class="card-body">
            <p class="card-title text-md-center text-xl-left">{{$title}}</p>
            <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center ">
                <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0"> {{number_format($value)}}</h3>
                <i class="{{$icon}} text-{{$class}} btn-icon icon-md mb-0 mb-md-3 mb-xl-0"></i>
            </div>
        </div>
    </div>
</div>