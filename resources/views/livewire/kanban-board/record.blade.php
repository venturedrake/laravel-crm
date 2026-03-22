<div class="card bg-base-100 rounded-lg p-5 mb-3 cursor-grab" id="{{ $record['id'] }}">
    <div class="grow-1 null">
        <div class="flex justify-between">
            <div>
                <span>{{ $record['title'] }}</span>
                @if($record['labels'])
                    <div class="mt-2">
                        @foreach($record['labels'] as $label)
                            <x-mary-badge value="{{ $label->name }}" class="text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                        @endforeach
                    </div>
                @endif
                <div class="mt-2">
                    <a href="{{ url(route('laravel-crm.'.\Illuminate\Support\Str::plural($model).'.show', $record['id'])) }}" class="link link-hover link-primary">{{ $record['number'] }}</a>
                </div>
            </div>
            <div class="ml-2">
                @switch($model)
                    @case('lead')
                        @canany(['view crm leads', 'edit crm leads', 'delete crm leads'])
                            <x-mary-dropdown  class="btn-xs btn-square" right>
                                @can('edit crm leads')
                                    <li>
                                        <a class="my-0.5 py-1.5 px-4 hover:text-inherit whitespace-nowrap" href="{{ route('laravel-crm.deals.create', ['model' => 'lead', 'id' => $record['id']]) }}" wire:navigate draggable="false">
                                            <span class="mary-hideable whitespace-nowrap truncate">{{ ucfirst(__('laravel-crm::lang.convert')) }}</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('view crm leads')
                                    <x-mary-menu-item link="{{ route('laravel-crm.leads.show', ['lead' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.view')) }}" />
                                @endcan
                                @can('edit crm leads')
                                    <x-mary-menu-item link="{{ route('laravel-crm.leads.edit', ['lead' => $record['id']]) }}" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                                @endcan
                                @can('delete crm leads')
                                    <x-mary-menu-item onclick="modalDeleteLead{{ $record['id'] }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                                @endcan
                            </x-mary-dropdown>
                        @endcanany
                    @break
                @endswitch
            </div>
        </div>
        <div class="flex justify-between mt-2">
            <div>
                @if($record['amount'])
                    {{ money($record['amount'], $record['currency']) }}
                @endif
            </div>
            <div>
                <x-mary-icon name="fas.user-circle" />
            </div>
        </div>
    </div>
</div>
<x-crm-delete-confirm model="lead" id="{{ $record['id'] }}" />
