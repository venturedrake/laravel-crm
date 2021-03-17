<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
          'name' => 'name',
          'label' => 'Name',
          'value' => old('name', $user->name ?? null)
        ])
        @include('laravel-crm::partials.form.text',[
          'name' => 'email',
          'label' => 'Email',
          'value' => old('email', $user->email ?? null)
        ])
        @include('laravel-crm::partials.form.password',[
          'name' => 'password',
          'label' => 'Password',
          'value' => old('password')
        ])
        @include('laravel-crm::partials.form.password',[
          'name' => 'password_confirmation',
          'label' => 'Confirm Password',
          'value' => old('password_confirmation')
        ])
        <input id="crm_access" type="checkbox" name="crm_access" {{ ($user->crm_access == 1) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
    </div>
    <div class="col-sm-6">
        ...
    </div>
</div>