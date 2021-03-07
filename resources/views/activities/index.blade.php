@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Activities</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.activities.create')) }}"><span class="fa fa-plus"></span> Add activity</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($activities as $activity)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.activities.show',$activity)) }}">
                        <td>{{ $activity->name }}</td>
                        <td class="disable-link text-right">
                            <a href="{{  route('laravel-crm.activities.show',$activity) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.activities.edit',$activity) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.activities.destroy',$activity) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="activity"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($activities instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $activities->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection