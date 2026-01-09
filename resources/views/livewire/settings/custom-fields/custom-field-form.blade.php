<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div wire:key="details" class="space-y-3">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
            <x-mary-select wire:model.live="type" label="{{ ucfirst(__('laravel-crm::lang.type')) }}" :options="$types" required />
            <x-mary-select wire:model="field_group_id" label="{{ ucfirst(__('laravel-crm::lang.group')) }}" :options="$groups" />
            <x-mary-input wire:model="default" label="{{ ucfirst(__('laravel-crm::lang.default')) }}" />
            <x-mary-toggle label="{{ ucfirst(__('laravel-crm::lang.required')) }}" wire:model="required" />
        </div>
    </x-mary-card>
</div>
<div>
   
    @switch($type)
        @case('select')
        @case('checkbox_multiple')
        @case('radio')
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.options')) }}" class="mb-5" separator>
                <div wire:key="options" class="space-y-3">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="px-0">{{ ucfirst(__('laravel-crm::lang.label')) }}</th>
                        <th class="pb-1 pt-3 pr-5">{{ ucfirst(__('laravel-crm::lang.order')) }}</th>
                        <th class="pb-1 pt-3 pr-5"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($options as $index => $option)
                        <tr wire:key="option-{{ $index }}">
                            <td class="px-0">
                                <x-mary-input wire:model="options.{{ $index }}.label" />
                            </td>
                            <td class="pr-5 max-w-1">
                                <x-mary-input wire:model="options.{{ $index }}.order" />
                            </td>
                            <td class="text-right">
                                <x-mary-button wire:click="removeOption({{ $index }})" class="btn-xs btn-error btn-square text-white" type="button" icon="fas.x" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td class="px-0" colspan="3">
                            <x-mary-button wire:click="addOption" label="{{ ucfirst(__('laravel-crm::lang.add_option')) }}" class="btn-sm" type="button" icon="fas.plus" />
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            </div>
            </x-mary-card>
            @break
    @endswitch
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.attached_to')) }}" separator>
        <div wire:key="models" class="space-y-3">
            @foreach(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\fieldModels() as $modelKey => $modelName)
                <x-mary-checkbox label="{{ $modelName }}" wire:model="models" value="{{ $modelKey }}" right />
                <hr />
            @endforeach
        </div>
    </x-mary-card>
</div>

