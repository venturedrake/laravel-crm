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
    </div>
    <div class="col-sm-6">
        ...
    </div>
</div>