<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use App\User;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Product;

class UserShow extends Component
{
    use Toast;

    public User $user;

    public string $dateFormat = 'Y-m-d';

    public string $timeFormat = 'H:i';

    public function mount(): void
    {
        $settings = app('laravel-crm.settings');
        $this->dateFormat = $settings->get('date_format', config('laravel-crm.date_format', 'Y-m-d'));
        $this->timeFormat = $settings->get('time_format', config('laravel-crm.time_format', 'H:i'));
    }

    public function delete($id)
    {
        /* if ($product = Product::find($id)) {
             $product->delete();

             $this->success(ucfirst(trans('laravel-crm::lang.product_deleted')), redirectTo: route('laravel-crm.products.index'));
         }*/
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-show');
    }
}
