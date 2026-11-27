<?php

namespace VentureDrake\LaravelCrm\Tests\Stubs;

use finfo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\File;

trait TestsAttachmentUploads
{
    protected function attachment(string $name, string $contents): UploadedFile
    {
        $file = UploadedFile::fake()->createWithContent($name, $contents);

        // Livewire's test upload copies the fake's MIME type, which defaults to a filename lookup.
        return $file->mimeType((new finfo(FILEINFO_MIME_TYPE))->file($file->getPathname()));
    }

    protected function assertNoAttachmentsStored(): void
    {
        expect(Storage::disk(config('laravel-crm.attachments.disk'))->allFiles())->toBeEmpty();
        expect(Storage::disk('public')->allFiles())->toBeEmpty();
        expect(File::count())->toBe(0);
        expect(Activity::count())->toBe(0);
    }

    protected function rejectAttachmentWrites(bool $throws): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $write = $disk->shouldReceive('put')->once();
        if ($throws) {
            $write->andThrow(UnableToWriteFile::atLocation('attachments'));
        } else {
            $write->andReturn(false);
        }

        Storage::set(config('laravel-crm.attachments.disk'), $disk);
    }
}
