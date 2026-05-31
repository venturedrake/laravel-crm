<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_file')) }}" separator>
        <div class="grid gap-4" wire:key="file-upload"
             @dragenter.prevent="dragDepth++; dragging = true"
             @dragover.prevent="dragging = true"
             @dragleave.prevent="dragDepth = Math.max(0, dragDepth - 1); if (dragDepth === 0) dragging = false"
             @drop.prevent="dragDepth = 0; dragging = false; onDrop($event)"
             x-data="{
                file: null,
                uploading: false,
                progress: 0,
                dragging: false,
                dragDepth: 0,
                allowed: @js($allowedMimes),
                maxKb: {{ $maxFileSizeKb }},
                maxLabel: '{{ $this->maxFileSizeLabel }}',
                dropError: null,
                select(event) {
                    const file = event.target.files[0] ?? null
                    if (!file) {
                        this.file = null
                        this.dropError = null
                        return
                    }
                    if (this.accept(file)) {
                        this.dropError = null
                        return
                    }
                    this.describeRejection(file)
                    if (this.$refs.file) this.$refs.file.value = null
                },
                describeRejection(file) {
                    const parts = file.name.split('.')
                    const ext = parts.length > 1 ? parts.pop().toLowerCase() : ''
                    const allowed = this.allowed.map(a => String(a).toLowerCase())
                    if (!allowed.includes(ext)) {
                        this.dropError = `${file.name} {{ __('laravel-crm::lang.invalid_file_type') }}`
                    } else {
                        this.dropError = `${file.name} exceeds {{ __('laravel-crm::lang.max_file_size') }} (${this.maxLabel})`
                    }
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
                    this.describeRejection(file)
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
            <label for="crm-file-dropzone"
                   class="border-2 border-dashed border-base-300 rounded-lg p-6 text-center cursor-pointer transition-colors hover:bg-base-200"
                   :class="dragging && 'border-primary bg-primary/10'">
                <div x-show="!file" class="flex flex-col items-center gap-2">
                    <x-bx-cloud-upload class="w-10 h-10 text-base-content/60" />
                    <span class="text-sm">{{ ucfirst(__('laravel-crm::lang.drop_file_here')) }}</span>
                    <p class="text-base-content/60 text-xs">{{ ucfirst(__('laravel-crm::lang.max_file_size')) }}: {{ $this->maxFileSizeLabel }} &middot; {{ ucfirst(__('laravel-crm::lang.allowed_file_types')) }}: {{ strtoupper(implode(', ', $allowedMimes)) }}</p>
                </div>
                <div x-show="file" class="flex flex-col items-center gap-2">
                    <span class="text-sm" x-text="file && file.name"></span>
                    <a href="#" class="link link-primary text-xs"
                       @click.prevent.stop="file = null; if ($refs.file) $refs.file.value = null; dropError = null">{{ ucfirst(__('laravel-crm::lang.remove_file')) }}</a>
                </div>
                <input type="file" x-ref="file" @change="select($event)"
                       id="crm-file-dropzone"
                       :disabled="uploading"
                       accept="{{ '.' . implode(',.', $allowedMimes) }}"
                       class="sr-only" />
            </label>
            <div x-show="uploading" class="grid gap-1">
                <div class="flex justify-between text-sm text-base-content/60">
                    <span>{{ ucfirst(__('laravel-crm::lang.upload')) }}ing...</span>
                    <span x-text="progress + '%'"></span>
                </div>
                <progress class="progress progress-primary w-full" x-bind:value="progress" max="100"></progress>
            </div>
            <div x-show="dropError" x-text="dropError" class="text-error text-sm"></div>
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
