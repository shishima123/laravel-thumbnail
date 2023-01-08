<?php

namespace PhuocNguyen\Thumbnail\Helper;

use Illuminate\Support\Facades\Storage;
use PhuocNguyen\Thumbnail\Exception\FileNotFound;

class StorageHelper
{
    public static function createDirectory($externalPath = '/', $disk = 'thumbnail'): bool
    {
        return Storage::disk($disk)->makeDirectory($externalPath);
    }

    public static function removeFile($fileName, $disk = 'temp_thumbnail'): bool
    {
        $fileName = pathinfo($fileName, PATHINFO_BASENAME);
        return Storage::disk($disk)->delete($fileName);
    }

    public static function cloneFileToTempDir($file, $fileName): string
    {
        if (empty($file)) {
            throw FileNotFound::make();
        }

        Storage::disk('temp_thumbnail')->putFileAs('/', $file, $fileName);

        return Storage::disk('temp_thumbnail')->path($fileName);
    }
}
