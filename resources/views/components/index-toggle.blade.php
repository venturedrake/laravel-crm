<div class="join">
    <x-mary-button icon="fas.list" class="join-item btn-outline {{ ($layout == 'index') ? 'btn-active' : null }}" link="{{ route('laravel-crm.'.$model.'.list') }}" />
    <x-mary-button icon="fas.th" class="join-item btn-outline {{ ($layout == 'board') ? 'btn-active' : null }}" link="{{ route('laravel-crm.'.$model.'.board') }}" />
</div>