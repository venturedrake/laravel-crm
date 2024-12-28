<div>
   <div class="row">
      <div class="col-sm-6">
         @include('laravel-crm::partials.form.select',[
               'name' => 'tax_rate_id',
               'label' => ucfirst(__('laravel-crm::lang.tax_rate')),
               'options' => ['' => ''] + \VentureDrake\LaravelCrm\Models\TaxRate::pluck('name', 'id')->toArray(),
               'attributes' => [
                'wire:model' => 'tax_rate_id'
               ]
           ])
      </div>
      <div class="col-sm-6">
         @include('laravel-crm::partials.form.text',[
               'name' => 'tax_rate',
               'label' => ucfirst(__('laravel-crm::lang.tax_rate_percent')),
               'append' => '<span class="fa fa-percent" aria-hidden="true"></span>',
               'attributes' => [
                   'wire:model' => 'tax_rate',
                   'readonly' => 'readonly'
               ]
           ])
      </div>
   </div>
</div>
