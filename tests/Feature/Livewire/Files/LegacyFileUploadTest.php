<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\Stubs\TestsAttachmentUploads;

uses(TestsAttachmentUploads::class);

beforeEach(function () {
    $this->actingAsUser();
    $this->lead = Lead::create(['title' => 'Test Lead']);
    config(['filesystems.default' => 'public', 'laravel-crm.attachments.disk' => 'crm-private']);
    Storage::fake('public');
    Storage::fake('crm-private');

    // Hosts with published v1 views can still mount the registered files component.
    View::addNamespace('laravel-crm', __DIR__.'/../../../../resources/v1/views');
});

test('legacy upload stores the original document on the configured attachment disk', function (string $setting, string $expectedDisk) {
    config(['laravel-crm.attachments.disk' => $setting]);
    Storage::fake('local');

    // FileObserver fills the creator on web requests. The v1 file row displays their name.
    File::creating(fn (File $file) => $file->user_created_id = auth()->id());
    $contents = 'Project meeting minutes.';

    Livewire::test('files', ['model' => $this->lead])
        ->set('file', $this->attachment('minutes.txt', $contents))
        ->call('upload')
        ->assertHasNoErrors()
        ->assertDispatched('fileUploaded')
        ->assertSet('file', null);

    $file = File::sole();
    expect($file->disk)->toBe($expectedDisk);
    expect($file->name)->toBe('minutes.txt');
    expect(basename($file->file))->not->toBe($file->name);
    expect(Storage::disk($file->disk)->get($file->file))->toBe($contents);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
    expect(Activity::sole()->recordable_id)->toEqual($file->id);
})->with([
    'configured disk' => ['crm-private', 'crm-private'],
    'blank disk setting' => ['', 'local'],
]);

test('legacy upload reports a failed permanent write before logging an upload', function (bool $throws) {
    $this->rejectAttachmentWrites($throws);

    Livewire::test('files', ['model' => $this->lead])
        ->set('file', $this->attachment('minutes.txt', 'Meeting minutes.'))
        ->call('upload')
        ->assertHasErrors('file')
        ->assertNotDispatched('fileUploaded')
        ->assertNotDispatched('notifyToast');

    expect(File::count())->toBe(0);
    expect(Activity::count())->toBe(0);
})->with(['disk returns false' => false, 'disk throws' => true]);

test('legacy upload requires create permission', function () {
    $this->actingAsUserWithPermissions(['view crm files']);

    Livewire::test('files', ['model' => $this->lead])
        ->set('file', $this->attachment('minutes.txt', 'Project meeting minutes.'))
        ->call('upload')
        ->assertForbidden();

    $this->assertNoAttachmentsStored();
});

test('legacy upload validates before persisting attachments', function (?string $name, string $contents, string $rule) {
    $component = Livewire::test('files', ['model' => $this->lead]);

    if ($name !== null) {
        $component->set('file', $this->attachment($name, $contents));
    }

    $component->call('upload')->assertHasErrors(['file' => $rule]);

    $this->assertNoAttachmentsStored();
})->with([
    'missing file' => [null, '', 'required'],
    'unlisted suffix' => ['minutes.unlisted', 'Project meeting minutes.', 'extensions'],
    'unlisted content' => ['minutes.txt', '<?xml version="1.0"?><minutes/>', 'mimes'],
    'one byte over limit' => fn () => ['minutes.pdf', str_pad("%PDF-1.4\n", 10 * 1024 * 1024 + 1), 'max'],
]);
