<div class="row">
    <div class="col border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'organisation_name',
         'label' => ucfirst(trans('laravel-crm::lang.organization_name')),
         'value' => old('organisation_name', $organisationName->value ?? null),
         'required' => 'true'
        ])

        @if($logoFile)
        <div class="mb-3">
            <img src=" {{ ($logoFile) ? asset('storage/'.$logoFile->value) : 'https://via.placeholder.com/140x90' }}" class="img-fluid" width="200" />
        </div>
        @endif
        @include('laravel-crm::partials.form.file',[
             'name' => 'logo',
             'label' => ucfirst(trans('laravel-crm::lang.logo')),
             'value' => old('logo', $timezone ?? null)
         ])
        @hasquotesenabled
        @include('laravel-crm::partials.form.text',[
         'name' => 'quote_prefix',
         'label' => ucfirst(trans('laravel-crm::lang.quote_prefix')),
         'value' => old('quote_prefix', $quotePrefix->value ?? null)
        ])
        @endhasquotesenabled
        @hasordersenabled
        @include('laravel-crm::partials.form.text',[
         'name' => 'order_prefix',
         'label' => ucfirst(trans('laravel-crm::lang.order_prefix')),
         'value' => old('order_prefix', $orderPrefix->value ?? null)
        ])
        @endhasordersenabled
        @hasinvoicesenabled
        @include('laravel-crm::partials.form.text',[
         'name' => 'invoice_prefix',
         'label' => ucfirst(trans('laravel-crm::lang.invoice_prefix')),
         'value' => old('invoice_prefix', $invoicePrefix->value ?? null)
        ])
        @endhasinvoicesenabled
        @hasquotesenabled
        @include('laravel-crm::partials.form.textarea',[
         'name' => 'quote_terms',
         'label' => ucfirst(trans('laravel-crm::lang.quote_terms')),
         'rows' => 5,
         'value' => old('quote_terms', $quoteTerms->value ?? null)
        ])
        @endhasquotesenabled
        @hasinvoicesenabled
        @include('laravel-crm::partials.form.textarea',[
         'name' => 'invoice_terms',
         'label' => ucfirst(trans('laravel-crm::lang.invoice_terms')),
         'rows' => 5,
         'value' => old('invoice_terms', $invoiceTerms->value ?? null)
        ])
        @endhasinvoicesenabled
    </div>
    <div class="col">
        @include('laravel-crm::partials.form.select',[
                'name' => 'country',
                'label' => ucfirst(trans('laravel-crm::lang.country')),
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries(),
                'value' => old('country', $country->value  ?? 'United States'),
                'required' => 'true'
             ])
        @include('laravel-crm::partials.form.select',[
           'name' => 'language',
           'label' => ucfirst(trans('laravel-crm::lang.language')),
           'options' => ['english' => 'English'],
           'value' => old('language', $language->value ?? 'english'),
           'required' => 'true'
        ])
        @include('laravel-crm::partials.form.select',[
           'name' => 'currency',
           'label' => ucfirst(trans('laravel-crm::lang.currency')),
           'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
           'value' => old('currency', $currency->value ?? 'USD'),
           'required' => 'true'
       ])
       @include('laravel-crm::partials.form.select',[
            'name' => 'timezone',
            'label' => ucfirst(trans('laravel-crm::lang.timezone')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timezones(),
            'value' => old('timezone', $timezone->value ?? null),
            'required' => 'true'
       ])
        @include('laravel-crm::partials.form.select',[
            'name' => 'date_format',
            'label' => ucfirst(trans('laravel-crm::lang.date_format')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\dateFormats(),
            'value' => old('date_format', $dateFormat->value ?? null),
            'required' => 'true'
       ])
        @include('laravel-crm::partials.form.select',[
            'name' => 'time_format',
            'label' => ucfirst(trans('laravel-crm::lang.time_format')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timeFormats(),
            'value' => old('time_format', $timeFormat->value ?? null),
            'required' => 'true'
       ])
        @include('laravel-crm::partials.form.select',[
            'name' => 'time_format',
            'label' => ucfirst(trans('laravel-crm::lang.time_format')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timeFormats(),
            'value' => old('time_format', $timeFormat->value ?? null),
            'required' => 'true'
       ])
        @include('laravel-crm::partials.form.text',[
            'name' => 'tax_name',
            'label' => ucfirst(trans('laravel-crm::lang.tax_name')),
            'value' => old('tax_name', $taxName->value ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
            'name' => 'tax_rate',
            'label' => ucfirst(trans('laravel-crm::lang.tax_rate')),
            'value' => old('tax_rate', $taxRate->value ?? null),
            'append' => '%'
       ])
        <div class="form-group">
            <label for="dynamic_products">{{ ucfirst(__('laravel-crm::lang.allow_creating_products_when_creating_quotes_orders_and_invoices')) }}</label>
            <span class="form-control-toggle">
                 <input id="dynamic_products" type="checkbox" name="dynamic_products" {{ (isset($dynamicProducts->value) && ($dynamicProducts->value == 1)) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
            </span>
        </div>
        <div class="form-group">
            <label for="show_related_activity">{{ ucfirst(__('laravel-crm::lang.show_related_contact_activity')) }}</label>
            <span class="form-control-toggle">
                 <input id="show_related_activity" type="checkbox" name="show_related_activity" {{ (isset($showRelatedActivity->value) && ($showRelatedActivity->value == 1)) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
            </span>
        </div>
    </div>
</div>
