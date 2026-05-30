<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Files\FileRelated;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lead;

beforeEach(function () {
    $this->user = $this->actingAsUser();
    $this->lead = Lead::create(['title' => 'Test Lead']);
    Storage::fake('local');
});

test('happy path uploads file, creates activity, dispatches events and resets uploadedFile', function () {
    $upload = UploadedFile::fake()->create('original.pdf', 1, 'application/pdf');

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('file-added')
        ->assertDispatched('activity-logged')
        ->assertSet('uploadedFile', null);

    $file = File::first();
    expect($file)->not->toBeNull();
    expect($file->name)->toBe('original.pdf');
    expect((int) $file->filesize)->toBe(1024);
    expect($file->mime)->toBe('application/pdf');
    expect($file->fileable_id)->toBe($this->lead->id);
    expect($file->fileable_type)->toBe($this->lead->getMorphClass());
    expect(Str::startsWith($file->file, 'laravel-crm/lead/'.$this->lead->id.'/files/'))->toBeTrue();

    Storage::disk('local')->assertExists($file->file);

    $activity = Activity::first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causeable_id)->toBe($this->user->id);
    expect($activity->causeable_type)->toBe($this->user->getMorphClass());
    expect((int) $activity->timelineable_id)->toBe($this->lead->id);
    expect($activity->timelineable_type)->toBe($this->lead->getMorphClass());
    expect((int) $activity->recordable_id)->toBe($file->id);
    expect($activity->recordable_type)->toBe($file->getMorphClass());
});

test('save without uploadedFile triggers required validation and creates no File row', function () {
    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->call('save')
        ->assertHasErrors(['uploadedFile' => 'required']);

    expect(File::count())->toBe(0);
    expect(Activity::count())->toBe(0);
});

test('save with file larger than 10 MB triggers max validation error', function () {
    $upload = UploadedFile::fake()->create('huge.pdf', 11 * 1024, 'application/pdf');

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasErrors(['uploadedFile' => 'max']);

    expect(File::count())->toBe(0);
    expect(Activity::count())->toBe(0);
});

test('saved File.name preserves the original client filename, not the hashed store() path', function () {
    $upload = UploadedFile::fake()->create('quarterly-report.pdf', 1, 'application/pdf');

    Livewire::test(FileRelated::class, ['model' => $this->lead])
        ->set('uploadedFile', $upload)
        ->call('save')
        ->assertHasNoErrors();

    $file = File::first();
    expect($file)->not->toBeNull();
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
