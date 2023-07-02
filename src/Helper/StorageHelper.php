<?php

namespace Shishima\Thumbnail\Helper;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Shishima\Thumbnail\Exception\FileNotFound;

class StorageHelper
{
    /**
     * Creates a directory on the specified disk.
     * @param  string  $externalPath  The path of the directory to be created (relative to the root of the disk)
     * @param  string  $disk  The name of the disk where the directory should be created
     * @return bool True if the directory was created, false otherwise
     */
    public static function createDirectory(string $externalPath = '/', string $disk = 'thumbnail'): bool
    {
        return Storage::disk($disk)->makeDirectory($externalPath);
    }

    /**
     * Rename a file from the old path to the new path.
     *
     * @param  string  $old  The old path of the file.
     * @param  string  $new  The new path of the file.
     * @return bool True if the file was successfully renamed, false otherwise.
     */
    public static function rename(string $old, string $new): bool
    {
        return File::move($old, $new);
    }

    /**
     * Deletes the specified file from the specified disk.
     * @param  string  $fileName  The name of the file to be deleted
     * @param  string  $disk  The name of the disk where the file is located
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
     * @param  UploadedFile  $file  The file to be copied
     * @param  string  $fileName  The name to give to the copied file
     * @return string The path of the copied file on the temp thumbnail disk
     * @throws FileNotFound If the file is not found
     */
    public static function cloneFileToTempDir(UploadedFile $file, string $fileName): string
    {
        Storage::disk('temp_thumbnail')->putFileAs('/', $file, $fileName);

        return Storage::disk('temp_thumbnail')->path($fileName);
    }
}
