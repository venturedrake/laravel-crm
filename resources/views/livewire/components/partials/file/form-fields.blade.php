@include('laravel-crm::partials.form.file',[
  'name' => 'file',
  'label' => ucfirst(__('laravel-crm::lang.file')),
  'attributes' => [
      'wire:model.defer' => 'file'  
  ]
])