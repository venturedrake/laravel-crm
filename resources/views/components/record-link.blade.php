@if($trashed())
    {{ $label }} <x-mary-badge :value="__('laravel-crm::lang.deleted')" class="badge-ghost badge-sm" />
@else
    <a href="{{ route($route, $record) }}" @if($class) class="{{ $class }}" @endif>{{ $label }}</a>
@endif
