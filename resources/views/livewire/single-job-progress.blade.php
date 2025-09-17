
<div class="container row mt-4">
    @if(!is_null($process) && !$process->finished)
    @php
        $progress = $process->progress;
    @endphp
    <div wire:poll.5s class="col-xl col-md mb-4">
        <div class="p-5">
            <div class="progress mt-4" style="height: 25px;">
                <div id="progress-bar" 
                    class="progress-bar" 
                    role="progressbar" 
                    style="width: {{$progress}}%;" 
                    aria-valuenow="{{$progress}}" 
                    aria-valuemin="0" 
                    aria-valuemax="100">{{$progress}}%
                </div>
            </div>
            <span class="text-success">Success: {{$process->processed}}</span>
            |
            <span class="text-warning">Pending: {{$process->pending}}</span>
            |
            <span class="text-danger">Failed: {{$process->failed}}</span>
            |
            <span class="text-secondary">Total: {{$process->total}}</span>
        </div>
    </div>
    @endif
</div>