<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(__('laravel-crm::lang.name')),
         'value' => old('name', $role->name ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
         'name' => 'description',
         'label' => ucfirst(__('laravel-crm::lang.description')),
         'value' => old('description', $role->description ?? null)
       ])
    </div>
    <div class="col-sm-6">
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.permission')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.description')) }}</th>
                <th scope="col" class="text-right">{{ ucwords(__('laravel-crm::lang.has_permission')) }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($permissions as $permission)
                <tr>
                    <td>{{ $permission->name }}</td>
                    <td>{{ $permission->description }}</td>
                    <td class="disable-link text-right">
                        <input id="permission_{{ $permission->id }}" type="checkbox" name="permission[{{ $permission->id }}]" {{ (isset($role) && $role->hasPermissionTo($permission->name)) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>