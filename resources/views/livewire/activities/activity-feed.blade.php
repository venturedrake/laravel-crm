<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.activities')) }}" separator>
        <x-slot:actions>
            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.my_activity')) }}"
                wire:click="setScope('mine')"
                class="btn-sm {{ $scope === 'mine' ? 'btn-primary' : 'btn-ghost' }}"
            />
            <x-mary-button
                label="{{ ucfirst(__('laravel-crm::lang.all_activity')) }}"
                wire:click="setScope('all')"
                class="btn-sm {{ $scope === 'all' ? 'btn-primary' : 'btn-ghost' }}"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-tabs wire:model.live="tab">
        @foreach(['all' => 'all', 'notes' => 'notes', 'tasks' => 'tasks', 'calls' => 'calls', 'meetings' => 'meetings', 'lunches' => 'lunches', 'files' => 'files'] as $tabName => $langKey)
            <x-mary-tab name="{{ $tabName }}" label="{{ ucfirst(__('laravel-crm::lang.' . $langKey)) }}">
                @if($tab === $tabName)
                    <x-mary-card>
                        @forelse($this->activities as $activity)
                            @php
                                $userName = $activity->causeable->name ?? $activity->recordable?->createdByUser?->name ?? null;
                                $activityType = $activity->recordable_type ? strtolower(class_basename($activity->recordable_type)) : null;
                            @endphp

                            <x-crm-timeline-item
                                :title="($userName ? $userName . ' created a ' : 'Created a ') . ($activityType ?? 'activity')"
                                :subtitle="$activity->created_at->format('m/d/Y h:i A')"
                                :activity="$activity"
                                :activityType="$activityType"
                                :first="$loop->first"
                                :last="$loop->last"
                            />
                        @empty
                            <div class="p-5 text-center text-gray-500">
                                {{ ucfirst(__('laravel-crm::lang.no_activities')) }}
                            </div>
                        @endforelse
                    </x-mary-card>

                    <div class="mt-4">
                        {{ $this->activities->links() }}
                    </div>
                @endif
            </x-mary-tab>
        @endforeach
    </x-mary-tabs>
</div>
