<x-mary-card shadow class="mt-5" title="{{ ucfirst(__('laravel-crm::lang.audience')) }}">
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-3">
            <x-mary-radio
                wire:model.live="audience_type"
                :options="$audienceTypeOptions"
                label="{{ ucfirst(__('laravel-crm::lang.audience')) }}"
            />
            @if($audience_type !== \VentureDrake\LaravelCrm\Models\EmailCampaign::AUDIENCE_ALL)
                <div wire:key="audience-ids-{{ $audience_type }}">
                    @if(\VentureDrake\LaravelCrm\Support\EmailCampaignAudience::isSearchable($audience_type))
                        <x-mary-choices
                            wire:model.live="audience_ids"
                            label="{{ ucfirst(\VentureDrake\LaravelCrm\Support\EmailCampaignAudience::typeLabel($audience_type)) }}"
                            :options="$audienceOptions"
                            search-function="searchAudience"
                            :min-chars="1"
                            placeholder="Search ..."
                            searchable />
                    @else
                        <x-mary-choices-offline
                            wire:model.live="audience_ids"
                            label="{{ ucfirst(\VentureDrake\LaravelCrm\Support\EmailCampaignAudience::typeLabel($audience_type)) }}"
                            :options="$audienceOptions"
                            placeholder="Search ..."
                            searchable />
                    @endif
                </div>
            @else
                @error('audience_ids')
                    <div class="text-error text-sm">{{ $message }}</div>
                @enderror
            @endif
        </div>
        <div wire:init="loadRecipientCount">
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.recipients')) }}"
                :value="$recipientCount === null ? '…' : number_format($recipientCount)"
                icon="o-users"
                wire:loading.class="opacity-50"
                wire:target="audience_type,audience_ids,loadRecipientCount"
            />
        </div>
    </div>
</x-mary-card>

<x-mary-card shadow class="mt-5" title="{{ ucfirst(__('laravel-crm::lang.send')) }}">
    <div class="grid lg:grid-cols-2 gap-5">
        <x-mary-radio
            wire:model.live="send_mode"
            :options="[
                ['id' => 'draft', 'name' => ucfirst(__('laravel-crm::lang.save_as_draft'))],
                ['id' => 'now', 'name' => ucfirst(__('laravel-crm::lang.send_now'))],
                ['id' => 'schedule', 'name' => ucfirst(__('laravel-crm::lang.schedule_send'))],
            ]"
            label="{{ ucfirst(__('laravel-crm::lang.send')) }}"
        />
        @if($send_mode === 'schedule')
            <x-mary-input wire:model="scheduled_at" type="datetime-local" label="{{ ucfirst(__('laravel-crm::lang.scheduled_at')) }}" />
        @endif
    </div>
</x-mary-card>
