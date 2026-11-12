{{-- Collapsed to a summary until opened. `expanded` lives on the component,
     so an email stays open when the list around it is refreshed. --}}
<div class="relative mt-2">
    @include('laravel-crm::livewire.email-messages.partials.body-style')

    <x-mary-collapse wire:model.live="expanded" class="border border-base-300 bg-base-100">
        <x-slot:heading class="text-lg" style="padding-inline-end: 6rem">
            <div class="flex flex-row items-start gap-3 min-w-0">
                <x-mary-icon
                    name="{{ $emailmessage->isOutbound() ? 'fas.paper-plane' : 'fas.inbox' }}"
                    class="mt-1 shrink-0 {{ $emailmessage->isOutbound() ? 'text-primary' : 'text-secondary' }}" />
                <div class="min-w-0">
                    <div class="break-words">{{ $emailmessage->subject }}</div>
                    <div class="flex flex-row flex-wrap gap-1 mt-1">
                        <x-mary-badge
                            value="{{ ucfirst(__('laravel-crm::lang.'.($emailmessage->isOutbound() ? 'sent' : 'received'))) }}"
                            class="badge-sm {{ $emailmessage->isOutbound() ? 'badge-primary text-white' : 'badge-secondary text-white' }}" />
                        @if($emailmessage->sent_at)
                            <x-mary-badge value="{{ $emailmessage->sent_at->format('h:i A') }} on {{ $emailmessage->sent_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                        @endif
                    </div>
                    <div class="text-sm font-normal mt-1 grid gap-1 break-words">
                        <div><span class="opacity-70">{{ ucfirst(__('laravel-crm::lang.from')) }}:</span> {{ $emailmessage->from_name }} {{ $emailmessage->from_name ? '<'.$emailmessage->from_address.'>' : $emailmessage->from_address }}</div>
                        <div><span class="opacity-70">{{ ucfirst(__('laravel-crm::lang.to')) }}:</span> {{ $emailmessage->to }}</div>
                    </div>
                    @if($related && $emailmessage->emailable)
                        <div class="flex flex-row items-center gap-2 mt-1 relative z-10">
                            @if(class_basename($emailmessage->emailable->getMorphClass()) == 'Person')
                                <x-mary-icon name="fas.user-circle" class="text-sm" />
                                <span class="text-sm">
                                    <a href="{{ route('laravel-crm.people.show', $emailmessage->emailable) }}" class="link link-hover link-primary">{{ $emailmessage->emailable->name }}</a>
                                </span>
                            @elseif(class_basename($emailmessage->emailable->getMorphClass()) == 'Organization')
                                <x-mary-icon name="fas.building" class="text-sm" />
                                <span class="text-sm">
                                    <a href="{{ route('laravel-crm.organizations.show', $emailmessage->emailable) }}" class="link link-hover link-primary">{{ $emailmessage->emailable->name }}</a>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </x-slot:heading>
        <x-slot:content>
            @if($expanded)
                <div class="grid gap-3">
                    @if($editing)
                        <x-mary-form wire:submit="update">
                            <div class="grid gap-3">
                                @include('laravel-crm::livewire.email-messages.partials.fields')
                            </div>
                            <x-slot:actions>
                                @can('delete crm emails')
                                    <x-mary-button onclick="modalDeleteEmailMessageItem{{ $emailmessage->id }}.showModal()" label="{{ ucfirst(__('laravel-crm::lang.delete')) }}" class="btn-error text-white" style="margin-inline-end: auto" type="button" />
                                @endcan
                                <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                                @can('edit crm emails')
                                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                                @endcan
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        {{-- From and To are in the summary above. --}}
                        <div class="text-sm grid gap-1 break-words">
                            @if($emailmessage->cc)
                                <div><span class="opacity-70">{{ ucfirst(__('laravel-crm::lang.cc')) }}:</span> {{ $emailmessage->cc }}</div>
                            @endif
                            @if($emailmessage->createdByUser)
                                <div><span class="opacity-70">{{ ucfirst(__('laravel-crm::lang.logged_by')) }}:</span> {{ $emailmessage->createdByUser->name }}</div>
                            @endif
                        </div>
                        @if(count($emailmessage->contacts) > 0)
                            <div class="flex flex-row gap-2 flex-wrap">
                                @foreach($emailmessage->contacts as $contact)
                                    @if($contact->entityable)
                                        <span class="text-sm">
                                            <x-mary-icon name="fas.user-circle" class="text-sm" />
                                            <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}" class="link link-hover link-primary">{{ $contact->entityable->name }}</a>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <hr />
                        {{-- Cleaned by EmailHtmlSanitizer in the component: an allowlist of
                             formatting tags, no scripts, styles, images or attributes. --}}
                        <div class="text-sm break-words crm-email-body">{!! $bodyHtml !!}</div>
                        @if($emailmessage->attachment_names)
                            <hr />
                            <div class="text-sm">
                                <x-mary-icon name="fas.paperclip" class="text-sm" />
                                {{ $emailmessage->attachment_names }}
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </x-slot:content>
    </x-mary-collapse>

    {{-- Outside the widget: it clips what overflows it, and a click inside
         its heading would open or close the item. --}}
    <div class="absolute top-3 z-10" style="inset-inline-end: 3rem">
        @can('edit crm emails')
            <x-mary-dropdown right top>
                <x-slot:trigger>
                    <x-mary-icon name="o-ellipsis-horizontal" />
                </x-slot:trigger>
                <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
            </x-mary-dropdown>
        @endcan
    </div>

    @can('delete crm emails')
        @include('laravel-crm::livewire.partials.activity-delete-confirm', ['dialogId' => 'modalDeleteEmailMessageItem'.$emailmessage->id, 'what' => 'email'])
    @endcan
</div>
