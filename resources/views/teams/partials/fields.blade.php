<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(__('laravel-crm::lang.name')),
         'value' => old('name', $team->name ?? null)
       ])
    </div>
    <div class="col-sm-6">
        <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.users')) }}</h6>
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                <th scope="col" class="text-right">{{ ucwords(__('laravel-crm::lang.on_team')) }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.users.show',$user)) }}">
                    <td>{{ $user->name }}</td>
                    <td class="disable-link text-right">
                        <input id="user_{{ $user->id }}" type="checkbox" name="user[{{ $user->id }}]" {{ (isset($team) && $user->belongsToCrmTeam($team)) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>