<?php

namespace VentureDrake\LaravelCrm\Livewire\Profile;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class UpdateProfileInformationForm extends Component
{
    use Toast;
    use WithFileUploads;

    public $name;

    public $email;

    public $photo;

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    protected function rules()
    {
        $user = auth()->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique($user->getTable(), 'email')->ignore($user->getKey(), $user->getKeyName()),
            ],
            'photo' => ['nullable', 'image', 'max:1024'],
        ];
    }

    public function updateProfileInformation()
    {
        $this->validate();

        $user = auth()->user();

        if ($this->photo) {
            $this->updatePhoto($user);
        }

        $user->forceFill([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if (in_array(MustVerifyEmail::class, class_implements($user))
            && $this->email !== $user->getOriginal('email')) {
            $user->email_verified_at = null;
            $user->save();
            if (method_exists($user, 'sendEmailVerificationNotification')) {
                $user->sendEmailVerificationNotification();
            }
        } else {
            $user->save();
        }

        $this->success(ucfirst(__('laravel-crm::lang.profile_updated')));
    }

    protected function updatePhoto($user)
    {
        // Jetstream-style profile_photo_path column
        if (! Schema::hasColumn($user->getTable(), 'profile_photo_path')) {
            return;
        }

        $disk = config('jetstream.profile_photo_disk', 'public');

        if ($user->profile_photo_path) {
            Storage::disk($disk)->delete($user->profile_photo_path);
        }

        $path = $this->photo->storePublicly('profile-photos', ['disk' => $disk]);

        $user->forceFill(['profile_photo_path' => $path])->save();

        $this->photo = null;
    }

    public function deletePhoto()
    {
        $user = auth()->user();

        if (! Schema::hasColumn($user->getTable(), 'profile_photo_path')) {
            return;
        }

        $disk = config('jetstream.profile_photo_disk', 'public');

        if ($user->profile_photo_path) {
            Storage::disk($disk)->delete($user->profile_photo_path);
            $user->forceFill(['profile_photo_path' => null])->save();
        }

        $this->success(ucfirst(__('laravel-crm::lang.photo_removed')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.profile.update-profile-information-form', [
            'user' => auth()->user(),
        ]);
    }
}
