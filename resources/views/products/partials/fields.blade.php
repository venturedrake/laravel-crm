<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
                    'name' => 'name',
                    'label' => ucfirst(__('laravel-crm::lang.name')),
                    'value' => old('name', $product->name ?? null),
                    'required' => 'true'
                ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'code',
                   'label' => ucfirst(__('laravel-crm::lang.product_code')),
                   'value' => old('code', $product->code ?? null)
               ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                   'name' => 'product_category',
                   'label' => ucfirst(__('laravel-crm::lang.category')),
                   'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\ProductCategory::all(), true),
                   'value' => old('product_category', $product->productCategory->id ?? null)
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'purchase_account',
                   'label' => ucfirst(__('laravel-crm::lang.purchase_account')),
                   'value' => old('purchase_account', $product->purchase_account ?? null)
               ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'sales_account',
                  'label' => ucfirst(__('laravel-crm::lang.sales_account')),
                  'value' => old('sales_account', $product->sales_account ?? null)
              ])
            </div>
        </div>
        
        @include('laravel-crm::partials.form.textarea',[
            'name' => 'description',
            'label' => ucfirst(__('laravel-crm::lang.description')),
            'rows' => 5,
            'value' => old('description', $product->description ?? null) 
       ])
    </div>
    <div class="col-sm-6">
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'unit',
                   'label' => ucfirst(__('laravel-crm::lang.unit')),
                   'value' => old('unit', $product->unit ?? null)
               ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'unit_price',
                     'label' => ucfirst(__('laravel-crm::lang.unit_price')),
                     'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                     'value' => old('unit_price', (isset($product) && (isset($product->getDefaultPrice()->unit_price)) ? ($product->getDefaultPrice()->unit_price / 100) : null) ?? null) 
                 ])
            </div>
        </div>
        @livewire('product-form',[
            'product' => $product ?? null
        ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                     'name' => 'currency',
                     'label' => ucfirst(__('laravel-crm::lang.currency')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                     'value' => old('currency', (isset($product) && (isset($product->getDefaultPrice()->currency)) ? $product->getDefaultPrice()->currency : null) ?? null ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
                 ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                'name' => 'user_owner_id',
                'label' => ucfirst(__('laravel-crm::lang.owner')),
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                'value' =>  old('user_owner_id', $product->user_owner_id ?? auth()->user()->id),
             ])
            </div>
        </div>
       
    </div>
</div>