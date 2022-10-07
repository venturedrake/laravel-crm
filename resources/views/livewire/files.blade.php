<div class="files">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.files')) }}</h6>
    <hr />--}}
    <form wire:submit.prevent="create" id="inputCreateForm">
        @include('laravel-crm::livewire.components.partials.file.form-fields')
        <div class="form-group">
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.upload')) }}</button>
        </div>
    </form>
    <hr/>
    <ul class="list-unstyled">
        @foreach($files as $file)
            {{--@livewire('file',[
                'file' => $file
            ], key($file->id))--}}
        @endforeach
    </ul>
</div>


