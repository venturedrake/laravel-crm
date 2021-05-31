<form method="POST" action="{{ url(route('laravel-crm.users.sendinvite')) }}">
    @csrf
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                {{ ucfirst(__('laravel-crm::lang.invite_user')) }}
            @endslot

            @slot('actions')
                    <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.users.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_users')) }}</a></span>
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

                <div class="row">
                    <div class="col-sm-12">
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'email',
                           'label' => ucfirst(__('laravel-crm::lang.email')),
                           'value' => old('email')
                         ])
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'subject',
                           'label' => ucfirst(__('laravel-crm::lang.subject')),
                           'value' => old('subject', 'Invitation to join Laravel CRM'),
                         ])
                        @include('laravel-crm::partials.form.textarea',[
                          'name' => 'message',
                          'label' => ucfirst(__('laravel-crm::lang.message')),
                          'rows' => 5,
                          'value' => old('message') 
                       ])
                    </div>
                </div>

        @endcomponent

        @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.users.index')) }}" class="btn btn-outline-secondary"> {{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
                <button type="submit" class="btn btn-primary"> {{ ucwords(__('laravel-crm::lang.send_invite')) }}</button>
        @endcomponent

    @endcomponent
</form>