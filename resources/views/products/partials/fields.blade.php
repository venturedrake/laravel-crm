<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
                    'name' => 'name',
                    'label' => 'Name',
                    'value' => old('name', $product->name ?? null)
                ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'code',
                   'label' => 'Product Code',
                   'value' => old('code', $product->code ?? null)
               ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                   'name' => 'product_category',
                   'label' => 'Category',
                   'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\ProductCategory::all(), true),
                   'value' => old('product_category', $product->productCategory->id ?? null)
               ])
            </div>
        </div>
        @include('laravel-crm::partials.form.textarea',[
            'name' => 'description',
            'label' => 'Description',
            'rows' => 5,
            'value' => old('description', $product->description ?? null) 
       ])
    </div>
    <div class="col-sm-6">
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'unit',
                   'label' => 'Unit',
                   'value' => old('unit', $product->unit ?? null)
               ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'unit_price',
                     'label' => 'Unit price',
                     'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                     'value' => old('unit_price', (isset($product) && (isset($product->getDefaultPrice()->unit_price)) ? ($product->getDefaultPrice()->unit_price / 100) : null) ?? null) 
                 ])
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'tax_rate',
                     'label' => 'Tax',
                     'append' => '<span class="fa fa-percent" aria-hidden="true"></span>',
                     'value' => old('tax_rate', $product->tax_rate ?? null)
                 ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => 'Currency',
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', (isset($product) && (isset($product->getDefaultPrice()->currency)) ? $product->getDefaultPrice()->currency : null) ?? null ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
                ])
            </div>
        </div>
        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_owner_id',
                 'label' => 'Owner',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_owner_id', $product->user_owner_id ?? auth()->user()->id),
              ])
    </div>
</div>