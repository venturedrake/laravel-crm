<div class="files">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.files')) }}</h6>
    <hr />--}}
    @if($showForm)
        <form wire:submit.prevent="upload" id="inputUploadForm">
            @include('laravel-crm::livewire.components.partials.file.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.upload')) }}</button>
            </div>
        </form>
    <hr/>
    @endif
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                window.addEventListener('fileUploaded', event => {
                    bsCustomFileInput.init()
                });
                window.addEventListener('fileAddOn', event => {
                    bsCustomFileInput.init()
                    $('.nav-activities li a#tab-files').tab('show')
                });
                window.addEventListener('addFileToggled', event => {
                    bsCustomFileInput.init()
                });
            });
        </script>
    @endpush
    <ul class="list-unstyled">
        @foreach($files as $file)
            @livewire('file',[
                'file' => $file
            ], key($file->id))
        @endforeach
    </ul>
</div>


