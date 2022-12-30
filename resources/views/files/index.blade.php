@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-activities')
        </div>
        <div class="card-body">
            <h3 class="mb-3"> {{ ucfirst(__('laravel-crm::lang.files')) }}</h3>
            @if($files && $files->count() > 0)
                @foreach($files as $file)
                    @livewire('file',[
                        'file' => $file
                    ], key($file->id))
                @endforeach
            @endif
        </div>
        @if($files instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $files->links() }}
            </div>
        @endif
    </div>

@endsection