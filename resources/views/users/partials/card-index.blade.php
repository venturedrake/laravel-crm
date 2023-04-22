@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.users')) }}
        @endslot

        @slot('actions')
            @can('create crm users')
            <span class="float-right">
                <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.users.create')) }}"><span class="fa fa-plus"></span> {{ ucfirst(__('laravel-crm::lang.add_user')) }}</a>
                <a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.users.invite')) }}"><span class="fa fa-paper-plane"></span> {{ ucfirst(__('laravel-crm::lang.invite_user')) }}</a>
            </span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.name')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.email')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.email_verified')) }}</th>
                <th scope="col">{{ __('laravel-crm::lang.CRM_Access') }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.role')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.created')) }}</th>
                {{-- <th scope="col">{{ ucfirst(__('laravel-crm::lang.updated')) }}</th>--}}
                <th scope="col">{{ ucwords(__('laravel-crm::lang.last_online')) }}</th>
                <th scope="col" width="150"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.users.show',$user)) }}">
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ ($user->email_verified_at) ? $user->email_verified_at->format($dateFormat.' '.$timeFormat) : null }}</td>
                    <td>{{ ($user->crm_access) ? 'Yes' : 'No' }}</td>
                    <td>
                        {{ $user->roles()->first()->name ?? null }}
                    </td>
                    <td>{{ $user->created_at->format($dateFormat) }}</td>
                    {{-- <td>{{ $user->updated_at->format($dateFormat) }}</td>--}}
                    <td>{{ ($user->last_online_at) ?  \Carbon\Carbon::parse($user->last_online_at)->diffForHumans() :  'Never' }}</td>
                    <td class="disable-link text-right">
                        @can('view crm users')
                        <a href="{{  route('laravel-crm.users.show',$user) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm users')
                        <a href="{{  route('laravel-crm.users.edit',$user) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm users')
                        <form action="{{ route('laravel-crm.users.destroy',$user) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.user') }}" {{ (auth()->user()->id == $user->id) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan    
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent

    @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $users->links() }}
        @endcomponent
    @endif

@endcomponent    