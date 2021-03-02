<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => 'Name',
         'value' => old('name', $team->name ?? null)
       ])
    </div>
    <div class="col-sm-6">
        <h6 class="text-uppercase">People</h6>
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.users.show',$user)) }}">
                    <td>{{ $user->name }}</td>
                    <td class="disable-link text-right">
                        <a href="#" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span>Add</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>