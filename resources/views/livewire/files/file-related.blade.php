<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_file')) }}" separator>
        <div class="grid gap-4" wire:key="file-upload"
             x-data="{
                file: null,
                uploading: false,
                progress: 0,
                dragging: false,
                allowed: @js($allowedMimes),
                maxKb: {{ $maxFileSizeKb }},
                maxLabel: '{{ $this->maxFileSizeLabel }}',
                dropError: null,
                select(event) {
                    this.file = event.target.files[0] ?? null
                },
                accept(file) {
                    if (!file) return false
                    const parts = file.name.split('.')
                    const ext = parts.length > 1 ? parts.pop().toLowerCase() : ''
                    const allowed = this.allowed.map(a => String(a).toLowerCase())
                    if (!allowed.includes(ext)) return false
                    if (file.size > this.maxKb * 1024) return false
                    this.file = file
                    if (this.$refs.file) {
                        const dt = new DataTransfer()
                        dt.items.add(file)
                        this.$refs.file.files = dt.files
                    }
                    return true
                },
                onDrop(event) {
                    event.preventDefault()
                    const file = event.dataTransfer && event.dataTransfer.files
                        ? event.dataTransfer.files[0]
                        : null
                    if (!file) return
                    if (this.accept(file)) {
                        this.dropError = null
                        return
                    }
                    const parts = file.name.split('.')
                    const ext = parts.length > 1 ? parts.pop().toLowerCase() : ''
                    const allowed = this.allowed.map(a => String(a).toLowerCase())
                    if (!allowed.includes(ext)) {
                        this.dropError = `${file.name} {{ __('laravel-crm::lang.invalid_file_type') }}`
                    } else {
                        this.dropError = `${file.name} exceeds {{ __('laravel-crm::lang.max_file_size') }} (${this.maxLabel})`
                    }
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
                       accept="{{ '.' . implode(',.', $allowedMimes) }}"
                       class="file-input w-full" />
            </fieldset>
            <p class="text-base-content/60 text-xs mt-1">{{ ucfirst(__('laravel-crm::lang.max_file_size')) }}: {{ $this->maxFileSizeLabel }} &middot; {{ ucfirst(__('laravel-crm::lang.allowed_file_types')) }}: {{ strtoupper(implode(', ', $allowedMimes)) }}</p>
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
