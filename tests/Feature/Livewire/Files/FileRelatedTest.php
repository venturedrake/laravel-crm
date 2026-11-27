<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Files\FileItem;
use VentureDrake\LaravelCrm\Livewire\Files\FileRelated;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\Stubs\TestsAttachmentUploads;

uses(TestsAttachmentUploads::class);

beforeEach(function () {
    config(['filesystems.default' => 'public']);
    Storage::fake('public');
    $this->user = $this->actingAsUser();
    $this->lead = Lead::create(['title' => 'Test Lead']);
    Storage::fake('local');
});

test('happy path uploads file, creates activity, dispatches events and resets uploadedFile', function (bool $blankDisk) {
    if ($blankDisk) {
        config(['laravel-crm.attachments.disk' => '']);
    }
    $contents = "Project meeting minutes.\n";
    $upload = $this->attachment('original.txt', $contents);

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('file-added')
        ->assertDispatched('activity-logged')
        ->assertSet('uploadedFile', null);

    $file = File::first();
    expect($file)->not->toBeNull();
    expect($file->name)->toBe('original.txt');
    expect((int) $file->filesize)->toBe(strlen($contents));
    expect($file->mime)->toBe('text/plain');
    expect($file->disk)->toBe('local');
    expect($file->fileable_id)->toBe($this->lead->id);
    expect($file->fileable_type)->toBe($this->lead->getMorphClass());
    expect(Str::startsWith($file->file, 'laravel-crm/lead/'.$this->lead->id.'/files/'))->toBeTrue();

    Storage::disk('local')->assertExists($file->file);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();

    Livewire::test(FileItem::class, ['file' => $file])
        ->call('download')
        ->assertFileDownloaded($file->name, $contents);

    $activity = Activity::first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causeable_id)->toBe($this->user->id);
    expect($activity->causeable_type)->toBe($this->user->getMorphClass());
    expect((int) $activity->timelineable_id)->toBe($this->lead->id);
    expect($activity->timelineable_type)->toBe($this->lead->getMorphClass());
    expect((int) $activity->recordable_id)->toBe($file->id);
    expect($activity->recordable_type)->toBe($file->getMorphClass());
})->with(['default disk' => false, 'blank disk setting' => true]);

test('save reports a failed permanent write before logging an upload', function (bool $throws) {
    $this->rejectAttachmentWrites($throws);

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $this->attachment('minutes.txt', 'Meeting minutes.'))
        ->call('save')
        ->assertHasErrors('uploadedFile')
        ->assertNotDispatched('file-added')
        ->assertNotDispatched('activity-logged');

    expect(File::count())->toBe(0);
    expect(Activity::count())->toBe(0);
})->with(['disk returns false' => false, 'disk throws' => true]);

test('save without uploadedFile triggers required validation and creates no File row', function () {
    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->call('save')
        ->assertHasErrors(['uploadedFile' => 'required']);

    $this->assertNoAttachmentsStored();
});

test('save accepts exactly 10 MiB', function () {
    $upload = $this->attachment('limit.pdf', str_pad("%PDF-1.4\n", 10 * 1024 * 1024));

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors();

    expect((int) File::sole()->filesize)->toBe(10 * 1024 * 1024);
});

test('save rejects one byte over 10 MiB', function () {
    $upload = $this->attachment('limit.pdf', str_pad("%PDF-1.4\n", 10 * 1024 * 1024 + 1));

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasErrors(['uploadedFile' => 'max']);

    $this->assertNoAttachmentsStored();
});

test('saved File.name preserves the original client filename, not the hashed store() path', function () {
    config(['laravel-crm.attachments.disk' => 'crm-private']);
    Storage::fake('crm-private');
    $upload = UploadedFile::fake()->create('quarterly-report.pdf', 1, 'application/pdf');

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors();

    $file = File::first();
    expect($file)->not->toBeNull();
    expect($file->disk)->toBe('crm-private');
    Storage::disk('crm-private')->assertExists($file->file);
    expect($file->name)->toBe('quarterly-report.pdf');
    expect($file->name)->not->toBe($file->file);
    expect(Str::endsWith($file->file, '.pdf'))->toBeTrue();
    expect(basename($file->file))->not->toBe('quarterly-report.pdf');
});

dataset('file mime types', [
    'pdf' => ['quarterly-report.pdf', 'application/pdf'],
    'png' => ['screenshot.png', 'image/png'],
    'docx' => ['proposal.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
]);

test('save records the correct mime for each upload type', function (string $filename, string $expectedMime) {
    $upload = UploadedFile::fake()->create($filename, 1, $expectedMime);

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors();

    $file = File::first();
    expect($file)->not->toBeNull();
    expect($file->mime)->toBe($expectedMime);
    expect($file->name)->toBe($filename);
})->with('file mime types');

dataset('allowed extensions', [
    'pdf' => ['report.pdf', 'application/pdf'],
    'png' => ['screenshot.png', 'image/png'],
    'docx' => ['proposal.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'xlsx' => ['budget.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'csv' => ['contacts.csv', 'text/csv'],
    'zip' => ['archive.zip', 'application/zip'],
]);

test('save accepts allowed file extensions and persists a File row', function (string $filename, string $mime) {
    $upload = UploadedFile::fake()->create($filename, 1, $mime);

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors();

    expect(File::count())->toBe(1);
    expect(File::first()->name)->toBe($filename);
})->with('allowed extensions');

dataset('disallowed extensions', [
    'exe' => ['evil.exe', 'application/octet-stream'],
    'sh' => ['malicious.sh', 'application/x-sh'],
    'php' => ['payload.php', 'application/x-php'],
    'js' => ['inject.js', 'application/javascript'],
]);

test('save rejects disallowed file extensions with mimes validation error', function (string $filename, string $mime) {
    $upload = UploadedFile::fake()->create($filename, 1, $mime);

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasErrors(['uploadedFile' => 'mimes']);

    $this->assertNoAttachmentsStored();
})->with('disallowed extensions');

test('upload policy properties cannot be updated by the client', function (string $property, mixed $value) {
    $component = Livewire::test(FileRelated::class, ['model' => $this->lead]);

    expect(fn () => $component->set($property, $value))->toThrow(PublicPropertyNotFoundException::class);
    $this->assertNoAttachmentsStored();
})->with([
    'allowed types' => ['allowedMimes', ['xml']],
    'maximum size' => ['maxFileSizeKb', 20480],
]);

test('save checks file content and submitted suffix independently', function (string $name, string $contents, string $rule) {
    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $this->attachment($name, $contents))
        ->call('save')
        ->assertHasErrors(['uploadedFile' => $rule]);

    $this->assertNoAttachmentsStored();
})->with([
    'plain text with unlisted suffix' => ['minutes.unlisted', 'Project meeting minutes.', 'extensions'],
    'XML document with allowed suffix' => ['minutes.txt', '<?xml version="1.0"?><minutes/>', 'mimes'],
]);

test('file uploader view renders the dropzone affordance', function () {
    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->assertSeeHtml('@drop.prevent')
        ->assertSeeHtml('maxKb: 10240')
        ->assertSee('10 MB')
        ->assertSeeHtml('accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.tsv,.txt,.zip"')
        ->assertSee(ucfirst(__('laravel-crm::lang.drop_file_here')));
});
