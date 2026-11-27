<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class AttachmentUpload
{
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'tsv', 'txt', 'zip'];

    public const MAX_SIZE_KB = 10240;

    public static function rules(): array
    {
        $extensions = implode(',', self::EXTENSIONS);

        return ['required', 'file', 'max:'.self::MAX_SIZE_KB, 'mimes:'.$extensions, 'extensions:'.$extensions];
    }

    public static function store(TemporaryUploadedFile $upload, Model $model, string $field): array
    {
        $disk = trim((string) config('laravel-crm.attachments.disk')) ?: 'local';
        $stream = null;

        try {
            $path = 'laravel-crm/'.strtolower(class_basename($model)).'/'.$model->id.'/files/'.$upload->hashName();
            $attributes = [
                'file' => $path,
                'disk' => $disk,
                'name' => $upload->getClientOriginalName(),
                'filesize' => $upload->getSize(),
                'mime' => $upload->getMimeType(),
            ];

            // Livewire's store() returns the path even when the write fails.
            $stream = $upload->readStream();
            if (is_resource($stream) && Storage::disk($disk)->put($path, $stream)) {
                return $attributes;
            }
        } catch (FilesystemException $exception) {
            report($exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        throw ValidationException::withMessages([
            $field => ucfirst(__('laravel-crm::lang.file_upload_failed')),
        ]);
    }
}
