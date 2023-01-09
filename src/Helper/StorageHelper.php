<?php

namespace PhuocNguyen\Thumbnail\Helper;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhuocNguyen\Thumbnail\Exception\FileNotFound;

class StorageHelper
{
    /**
     * Creates a directory on the specified disk.
     * @param string $externalPath The path of the directory to be created (relative to the root of the disk)
     * @param string $disk The name of the disk where the directory should be created
     * @return bool True if the directory was created, false otherwise
     */
    public static function createDirectory(string $externalPath = '/', string $disk = 'thumbnail'): bool
    {
        return Storage::disk($disk)->makeDirectory($externalPath);
    }

    /**
     * Deletes the specified file from the specified disk.
     * @param string $fileName The name of the file to be deleted
     * @param string $disk The name of the disk where the file is located
     * @return bool True if the file was deleted, false otherwise
     */
    public static function removeFile(string $fileName, string $disk = 'temp_thumbnail'): bool
    {
        $fileName = pathinfo($fileName, PATHINFO_BASENAME);
        return Storage::disk($disk)->delete($fileName);
    }

    /**
     *
     * Copies the given file to the temp thumbnail disk.
     *
     * @param UploadedFile $file The file to be copied
     * @param string $fileName The name to give to the copied file
     * @return string The path of the copied file on the temp thumbnail disk
     * @throws FileNotFound If the file is not found
     */
    public static function cloneFileToTempDir(UploadedFile $file, string $fileName): string
    {
        if (empty($file)) {
            throw FileNotFound::make();
        }

        Storage::disk('temp_thumbnail')->putFileAs('/', $file, $fileName);

        return Storage::disk('temp_thumbnail')->path($fileName);
    }
}
