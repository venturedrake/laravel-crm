<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
          'name' => 'name',
          'label' => ucfirst(__('laravel-crm::lang.name')),
          'value' => old('name', $user->name ?? null)
        ])
        @include('laravel-crm::partials.form.text',[
          'name' => 'email',
          'label' => ucfirst(__('laravel-crm::lang.email')),
          'value' => old('email', $user->email ?? null)
        ])
        @include('laravel-crm::partials.form.password',[
          'name' => 'password',
          'label' => ucfirst(__('laravel-crm::lang.password')),
          'value' => old('password')
        ])
        @include('laravel-crm::partials.form.password',[
          'name' => 'password_confirmation',
          'label' => ucfirst(__('laravel-crm::lang.confirm_password')),
          'value' => old('password_confirmation')
        ])
        <div class="form-group">
            <label for="crm_access">{{ ucfirst(__('laravel-crm::lang.CRM_access')) }}</label>
            <span class="form-control-toggle">
                 <input id="crm_access" type="checkbox" name="crm_access" {{ (isset($user) && ($user->crm_access == 1 || $user->isCrmOwner())) ? 'checked' : null }} {{ (isset($user) && $user->isCrmOwner()) ? 'disabled' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
            </span>
            </div>
        @if(isset($user) && $user->isCrmOwner())
            @include('laravel-crm::partials.form.select',[
               'name' => 'role',
               'label' => ucfirst(__('laravel-crm::lang.CRM_role')),
               'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(VentureDrake\LaravelCrm\Models\Role::crm()->get()),
               'value' => old('role', $user->roles()->first()->id ?? null),
               'attributes' => [
                   'disabled' => 'disabled'
               ]
            ])
        @else
            @include('laravel-crm::partials.form.select',[
                'name' => 'role',
                'label' => ucfirst(__('laravel-crm::lang.CRM_role')),
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(VentureDrake\LaravelCrm\Models\Role::crm()->get()),
                'value' => old('role', ((isset($user)) ? $user->roles()->first()->id : null)),
            ]) 
        @endif
    </div>
    <div class="col-sm-6">
        
    </div>
</div>