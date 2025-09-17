<div class="container">
    <form wire:submit.prevent="send">

        @include('shared.input',['type'=>'file','label'=>'Mail List', 'name'=>'file'])
        
        @include('shared.input',['label'=>'Subject', 'name'=>'subject'])

        @include('shared.input',['type'=>'textarea','label'=>'Message', 'name'=>'message'])
     
        <button class="btn btn-outline-primary btn-rounded" type="submit">
            @if($submitted)
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Mailing...
            @else
            Send
            @endif
        </button>
    </form>
</div>
