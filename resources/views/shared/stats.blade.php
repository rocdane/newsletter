@php
$name ??= 'null';
$percent ??= 0;
$value ??= 0;
@endphp
<tr>
    <td class="text-muted">{{$name}}</td>
    <td class="w-100 px-0">
        <div class="progress progress-md mx-4">
            <div class="progress-bar bg-primary" role="progressbar" style="width: {{$percent}}%" aria-valuenow="{{$percent}}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </td>
    <td><h5 class="font-weight-bold mb-0">{{$value}}</h5></td>
</tr>