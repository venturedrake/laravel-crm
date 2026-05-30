<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_file')) }}" separator>
        <div class="grid gap-4" wire:key="file-upload"
             x-data="{
                file: null,
                uploading: false,
                progress: 0,
                select(event) {
                    this.file = event.target.files[0] ?? null
                },
                submit() {
                    if (!this.file || this.uploading) return
                    this.uploading = true
                    this.progress = 0
                    $wire.upload('uploadedFile', this.file,
                        () => {
                            this.uploading = false
                            this.progress = 100
                            $wire.save().then(() => {
                                this.file = null
                                this.progress = 0
                                if (this.$refs.file) this.$refs.file.value = null
                            })
                        },
                        () => {
                            this.uploading = false
                            this.progress = 0
                        },
                        (event) => {
                            this.progress = event.detail.progress
                        }
                    )
                }
             }">
            <fieldset class="fieldset py-0">
                <legend class="fieldset-legend mb-0.5">{{ ucfirst(__('laravel-crm::lang.choose_file')) }}</legend>
                <input type="file" x-ref="file" @change="select($event)"
                       :disabled="uploading"
                       class="file-input w-full" />
            </fieldset>
            <div x-show="uploading" class="grid gap-1">
                <div class="flex justify-between text-sm text-base-content/60">
                    <span>{{ ucfirst(__('laravel-crm::lang.upload')) }}ing...</span>
                    <span x-text="progress + '%'"></span>
                </div>
                <progress class="progress progress-primary w-full" x-bind:value="progress" max="100"></progress>
            </div>
            @error('uploadedFile')
                <div class="text-error text-sm">{{ $message }}</div>
            @enderror
            <div>
                <x-mary-button @click="submit()" label="{{ ucfirst(__('laravel-crm::lang.upload')) }}"
                               class="btn-primary text-white"
                               ::disabled="!file || uploading" />
            </div>
        </div>
    </x-mary-card>

    @if(count($files) > 0)
        @foreach($files as $file)
            @livewire('crm-file-item', ['file' => $file, 'related' => $data[$file->id]['related']], key('file-item-'.$file->id))
        @endforeach
    @endif
</div>
