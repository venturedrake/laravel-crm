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
                <th scope="col" class="text-right">On Team</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.users.show',$user)) }}">
                    <td>{{ $user->name }}</td>
                    <td class="disable-link text-right">
                        <input id="user_{{ $user->id }}" type="checkbox" name="user[{{ $user->id }}]" {{ (isset($team) && $user->belongsToTeam($team)) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>