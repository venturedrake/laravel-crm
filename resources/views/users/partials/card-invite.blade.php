<form method="POST" action="{{ url(route('laravel-crm.users.sendinvite')) }}">
    @csrf
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                Create user
            @endslot

            @slot('actions')
                    <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.users.index')) }}"><span class="fa fa-angle-double-left"></span> Back to users</a></span>
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

                <div class="row">
                    <div class="col-sm-12">
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'email',
                           'label' => 'Email',
                           'value' => old('email')
                         ])
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'subject',
                           'label' => 'Subject',
                           'value' => old('subject', 'Invitation to join Laravel CRM'),
                         ])
                        @include('laravel-crm::partials.form.textarea',[
                          'name' => 'message',
                          'label' => 'Message',
                          'rows' => 5,
                          'value' => old('message') 
                       ])
                    </div>
                </div>

        @endcomponent

        @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.users.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Send Invite</button>
        @endcomponent

    @endcomponent
</form>