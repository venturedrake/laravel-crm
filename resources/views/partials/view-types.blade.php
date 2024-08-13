<div class="btn-group" role="group" aria-label="Switch view">
    <a href="{{ route('laravel-crm.leads.list') }}" type="button" class="btn btn-sm btn-outline-secondary" data-toggle="tooltip" data-placement="top" title="View {{ $model ?? null }} as list"><i class="fa fa-solid fa-list"></i></a>
    <a href="{{ route('laravel-crm.leads.board') }}" type="button" class="btn btn-sm btn-outline-secondary" data-toggle="tooltip" data-placement="top" title="View {{ $model ?? null }} as board"><i class="fa fas fa-th"></i></a>
</div>