<div class="files">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.files')) }}</h6>
    <hr />--}}
    <form wire:submit.prevent="upload" id="inputUploadForm">
        @include('laravel-crm::livewire.components.partials.file.form-fields')
        <div class="form-group">
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.upload')) }}</button>
        </div>
    </form>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                window.addEventListener('fileUploaded', event => {
                    bsCustomFileInput.init()
                });
            });
        </script>
    @endpush
    <hr/>
    <ul class="list-unstyled">
        @foreach($files as $file)
            @livewire('file',[
                'file' => $file
            ], key($file->id))
        @endforeach
    </ul>
</div>


