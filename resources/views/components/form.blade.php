<form method="{{ $method }}" action="{{ $action }}">
    @csrf
    {{ $slot }}
</form>