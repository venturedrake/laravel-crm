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
