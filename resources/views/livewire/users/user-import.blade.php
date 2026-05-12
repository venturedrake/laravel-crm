<div class="crm-content"
     x-data
     x-on:crm-process-import-chunk.window="$wire.processNextChunk()">

    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.import_users')) }}" class="mb-5" progress-indicator>
        <x-slot:actions>
            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.download_sample_csv')) }}"
                link="{{ url(route('laravel-crm.users.import.sample')) }}"
                icon="o-arrow-down-tray"
                class="btn-sm btn-outline"
                responsive />
            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.back_to_users')) }}"
                link="{{ url(route('laravel-crm.users.index')) }}"
                icon="fas.angle-double-left"
                class="btn-sm"
                responsive />
        </x-slot:actions>
    </x-mary-header>

    {{-- SUCCESS STATE --}}
    @if($imported)
        <x-mary-card shadow class="mb-5">
            <div class="flex flex-col items-center gap-4 py-8 text-center">
                <x-bx-check-circle class="w-16 h-16 text-success" />
                <h2 class="text-2xl font-bold">{{ ucfirst(__('laravel-crm::lang.import_complete')) }}</h2>
                <p class="text-base-content/70">
                    <span class="font-semibold text-success">{{ $importedCount }}</span> {{ __('laravel-crm::lang.users_imported_successfully') }},
                    <span class="font-semibold text-warning">{{ $skippedCount }}</span> {{ __('laravel-crm::lang.rows_skipped') }}.
                </p>
                <div class="flex gap-3 mt-2">
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.import_more')) }}" wire:click="resetForm" icon="o-arrow-up-tray" class="btn-primary text-white" />
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.view_users')) }}" link="{{ url(route('laravel-crm.users.index')) }}" icon="o-users" class="btn-outline" />
                </div>
            </div>
        </x-mary-card>

    {{-- PROGRESS STATE --}}
    @elseif($processing)
        <x-mary-card shadow class="mb-5">
            <div class="flex flex-col items-center gap-6 py-10 text-center">
                <span class="loading loading-spinner loading-lg text-primary"></span>
                <div class="w-full max-w-md">
                    <div class="flex justify-between text-sm text-base-content/70 mb-2">
                        <span>{{ ucfirst(__('laravel-crm::lang.importing')) }}…</span>
                        <span>{{ $pendingOffset }} / {{ $totalToProcess }}</span>
                    </div>
                    <progress
                        class="progress progress-primary w-full"
                        value="{{ $pendingOffset }}"
                        max="{{ $totalToProcess }}"></progress>
                    <div class="flex justify-between text-xs text-base-content/50 mt-2">
                        <span><span class="text-success font-semibold">{{ $importedCount }}</span> {{ __('laravel-crm::lang.imported') }}</span>
                        <span><span class="text-warning font-semibold">{{ $skippedCount }}</span> {{ __('laravel-crm::lang.skipped') }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

    {{-- PREVIEW TABLE --}}
    @elseif($hasPreview && count($previewRows) > 0)
        @php
            $allRows     = session('crm_user_import_preview', []);
            $validRows   = collect($allRows)->filter(fn ($r) => empty($r['errors']))->count();
            $invalidRows = collect($allRows)->filter(fn ($r) => ! empty($r['errors']))->count();
        @endphp

        <x-mary-card shadow class="mb-5" title="{{ ucfirst(__('laravel-crm::lang.preview')) }} ({{ $totalRows }} {{ __('laravel-crm::lang.rows') }})" separator>
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra w-full">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.email')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.CRM_access')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.role')) }}</th>
                            <th>{{ ucfirst(__('laravel-crm::lang.status')) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewRows as $row)
                            <tr class="{{ ! empty($row['errors']) ? 'bg-error/10' : 'bg-success/5' }}">
                                <td class="text-base-content/50 text-xs">{{ $row['row'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['email'] }}</td>
                                <td>
                                    @if($row['crm_access'])
                                        <span class="badge badge-success badge-sm">{{ ucfirst(__('laravel-crm::lang.yes')) }}</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">{{ ucfirst(__('laravel-crm::lang.no')) }}</span>
                                    @endif
                                </td>
                                <td>{{ $row['role'] ?: '—' }}</td>
                                <td>
                                    @if(! empty($row['errors']))
                                        <div class="text-error text-xs space-y-0.5">
                                            @foreach($row['errors'] as $err)
                                                <div>⚠ {{ $err }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="badge badge-success badge-sm">{{ ucfirst(__('laravel-crm::lang.ready')) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($lastPage > 1)
                <div class="flex items-center justify-center gap-2 mt-4 pt-3 border-t border-base-300">
                    <x-mary-button icon="o-chevron-left" wire:click="prevPage" class="btn-sm btn-outline btn-square" :disabled="$currentPage <= 1" />
                    <span class="text-sm text-base-content/70">
                        {{ ucfirst(__('laravel-crm::lang.page')) }} {{ $currentPage }} / {{ $lastPage }}
                        &nbsp;&bull;&nbsp;
                        {{ __('laravel-crm::lang.rows') }} {{ (($currentPage - 1) * $perPage) + 1 }}–{{ min($currentPage * $perPage, $totalRows) }} {{ __('laravel-crm::lang.of') }} {{ $totalRows }}
                    </span>
                    <x-mary-button icon="o-chevron-right" wire:click="nextPage" class="btn-sm btn-outline btn-square" :disabled="$currentPage >= $lastPage" />
                </div>
            @endif

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-base-300">
                <div class="flex items-center gap-3">
                    <div class="text-sm text-base-content/70">
                        <span class="text-success font-semibold">{{ $validRows }}</span> {{ __('laravel-crm::lang.rows_ready_to_import') }}
                        @if($invalidRows > 0)
                            &bull; <span class="text-error font-semibold">{{ $invalidRows }}</span> {{ __('laravel-crm::lang.rows_will_be_skipped') }}
                        @endif
                    </div>
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" wire:click="resetForm" icon="o-x-mark" class="btn-sm btn-outline" />
                </div>
                <div class="flex items-center gap-3">
                    <x-mary-select
                        wire:model="defaultRole"
                        :options="$roles"
                        label="{{ ucfirst(__('laravel-crm::lang.default_role_for_import')) }}"
                        option-value="id"
                        option-label="name"
                        class="select-sm"
                        inline />
                    @if($validRows > 0)
                        <x-mary-button
                            label="{{ ucfirst(__('laravel-crm::lang.import_users')) }} ({{ $validRows }})"
                            onclick="modalImportUsers.showModal()"
                            icon="o-arrow-up-tray"
                            class="btn-primary text-white" />

                        <dialog id="modalImportUsers" class="modal">
                            <div class="modal-box text-left">
                                <h3 class="text-lg font-bold">{{ ucfirst(__('laravel-crm::lang.import_users')) }}?</h3>
                                <p class="py-4">{{ __('laravel-crm::lang.import_confirm', ['count' => $validRows]) }}</p>
                                <div class="modal-action">
                                    <form method="dialog">
                                        <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                                        <button wire:click="startImport" class="btn btn-primary text-white">{{ ucfirst(__('laravel-crm::lang.import_users')) }}</button>
                                    </form>
                                </div>
                            </div>
                        </dialog>
                    @endif
                </div>
            </div>
        </x-mary-card>

    {{-- UPLOAD FORM (plain HTML — never touches Livewire file handling) --}}
    @else
        <x-mary-card shadow class="mb-5" title="{{ ucfirst(__('laravel-crm::lang.upload_csv_file')) }}" separator>
            <div class="grid gap-4">

                {{-- Validation errors from the controller --}}
                @if($errors->has('csv_file'))
                    <div class="alert alert-error text-sm">
                        <x-bx-error class="w-5 h-5 shrink-0" />
                        <span>{{ $errors->first('csv_file') }}</span>
                    </div>
                @endif

                <div class="alert alert-info text-sm">
                    <x-bx-info-circle class="w-5 h-5 shrink-0" />
                    <div>
                        <p class="font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.required_columns')) }}:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li><code>name</code> — {{ __('laravel-crm::lang.import_col_name') }}</li>
                            <li><code>email</code> — {{ __('laravel-crm::lang.import_col_email') }}</li>
                        </ul>
                        <p class="font-semibold mt-2 mb-1">{{ ucfirst(__('laravel-crm::lang.optional_columns')) }}:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li><code>crm_access</code> — {{ __('laravel-crm::lang.import_col_crm_access') }} (1 / 0)</li>
                            <li><code>role</code> — {{ __('laravel-crm::lang.import_col_role') }}</li>
                        </ul>
                        <p class="mt-2 text-xs opacity-70">{{ __('laravel-crm::lang.import_password_note') }}</p>
                        <p class="mt-2">
                            <x-mary-button
                                label="{{ ucfirst(__('laravel-crm::lang.download_sample_csv')) }}"
                                link="{{ url(route('laravel-crm.users.import.sample')) }}"
                                icon="o-arrow-down-tray"
                                class="btn-xs btn-outline mt-1" />
                        </p>
                    </div>
                </div>

                {{-- Standard HTML form — file goes directly to the controller, never through Livewire --}}
                <form method="POST" action="{{ route('laravel-crm.users.import.parse') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex flex-col gap-3">
                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text font-medium">{{ ucfirst(__('laravel-crm::lang.choose_csv_file')) }}</span>
                                <span class="label-text-alt text-base-content/50">{{ __('laravel-crm::lang.max_file_size_10mb') }}</span>
                            </div>
                            <input
                                type="file"
                                name="csv_file"
                                accept=".csv,text/csv"
                                class="file-input file-input-bordered w-full"
                                required />
                        </label>
                        <div>
                            <button type="submit" class="btn btn-primary text-white">
                                <x-bx-upload class="w-4 h-4" />
                                {{ ucfirst(__('laravel-crm::lang.upload_and_preview')) }}
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </x-mary-card>
    @endif
</div>

