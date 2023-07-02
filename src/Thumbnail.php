<?php

namespace Shishima\Thumbnail;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use ImagickException;
use Shishima\Thumbnail\Exception\FileFormatInvalid;
use Shishima\Thumbnail\Exception\FileNotFound;
use Shishima\Thumbnail\Exception\FileUploadNotSuccess;
use Shishima\Thumbnail\Helper\OfficeHelper;
use Shishima\Thumbnail\Helper\StorageHelper;
use Throwable;

/**
 * Class for generating thumbnails from files.
 *
 * This class can be used to generate thumbnails from various types of files, including Microsoft Office
 * files and PDFs. It uses the Imagick library to process the files and the unoconv command-line utility
 * to convert Microsoft Office files to PDFs if necessary.
 *
 * @package Shishima\Thumbnail
 */
class Thumbnail
{
    /**
     *  The file to generate the thumbnail from. This can be either a string
     *
     * @var string|UploadedFile
     */
    protected string|UploadedFile $file;

    /**
     *  The name of the file.
     *
     * @var string
     */
    protected string $fileName;

    /**
     *  The original name of the file.
     */
    protected string $originName;

    /**
     *  The extension of the file.
     */
    protected string $fileExtension = '';

    /**
     *  The height of the thumbnail.
     */
    protected int $height;

    /**
     *  The width of the thumbnail.
     */
    protected int $width;

    /**
     *  The output format for the thumbnail.
     */
    protected string $format;

    /**
     *  The layer method to use when merging image layers.
     */
    protected int $layer;

    /**
     *  Indicates if the temporary file should be removed after processing.
     */
    protected bool $shouldRemoveTempFile = true;

    /**
     *  An array of file extensions to ignore when generating thumbnails.
     */
    protected array $ignore = [];

    /**
     *  The Imagick instance to use for processing the files.
     */
    protected Imagick $imagick;

    /**
     *  An array of valid file extensions that can be used to generate thumbnails.
     */
    protected array $validExtensions = [
        'doc',
        'docx',
        'xls',
        'xlsx',
        'pdf',
        'gif',
        'jpg',
        'jpeg',
        'png'
    ];

    /**
     * Thumbnail constructor.
     *
     * @param  Imagick  $imagick  The Imagick instance to use for processing the files
     */
    public function __construct(Imagick $imagick)
    {
        $this->imagick = $imagick;
    }

    /**
     * Generates a thumbnail from the given file.
     *
     * @param  string|UploadedFile|null  $file  The file to generate the thumbnail
     * @return array An array containing the name, original name, and path of the generated thumbnail
     * @throws ImagickException|Exception|Throwable If an error occurs while processing the file
     */
    public function create(string|UploadedFile $file = null): array
    {
        if ( ! empty($file))
        {
            $this->setFile($file);
        }

        if ( ! $this->isFileCanGenerateThumbnail())
        {
            return $this->getDefaultPath();
        }

        $tempPath = StorageHelper::cloneFileToTempDir($this->getFile(), $this->getFileName());
        $tempPath = OfficeHelper::pretreatmentOfficeFile($tempPath, $this->getFileExtension());

        return $this->processing($tempPath);
    }

    /**
     * Processes the file to generate the thumbnail.
     *
     * This method reads the file using Imagick, resizes it to the specified dimensions, and sets the output format.
     * It then merges the image layers using the specified layer method and writes the resulting image to the
     * thumbnail disk.
     *
     * @param  string  $tempPath  The path of the file to be processed
     * @return array An array containing the name, original name, and path of the generated thumbnail
     * @throws ImagickException|FileFormatInvalid If an error occurs while processing the file
     */
    protected function processing(string $tempPath): array
    {
        $fileName = Str::of(pathinfo($tempPath, PATHINFO_FILENAME))->beforeLast('_')->append('_thumbnail_')->append(uniqid())->append('.')->append($this->getFormat())->snake();

        $fileOutPutDir = config('filesystems.disks.thumbnail.root');
        StorageHelper::createDirectory();
        $fileOutPut = $fileOutPutDir.'/'.$fileName;

        $this->imagick->readImage($tempPath);
        $this->imagick->thumbnailImage($this->getWidth(), $this->getHeight());
        $this->imagick->setFormat($this->getFormat());
        $im = $this->imagick->mergeImageLayers($this->getLayer());

        if ($im->writeImage($fileOutPut) === false)
        {
            return $this->getDefaultPath();
        }

        $this->imagick->clear();

        if ($this->shouldRemoveTempFile)
        {
            StorageHelper::removeFile($tempPath);
        }

        return [
            'name' => $fileName->value(),
            'original_name' => $this->getFileOriginalName(),
            'path' => Storage::disk('thumbnail')->url($fileName)
        ];
    }

    /**
     * Determines if the file is valid and able to be processed.
     *
     * This method checks if the file is an instance of `UploadedFile`, if it has a valid file extension, and if it
     * exists on the filesystem.
     *
     * @return bool `true` if the file is valid and able to be processed, `false` otherwise
     */
    protected function isFileCanGenerateThumbnail(): bool
    {
        $extensionCompare = array_diff($this->validExtensions, $this->getIgnore());
        return in_array($this->getFileExtension(), $extensionCompare, true);
    }

    /**
     * Perform pre-processing tasks on the file.
     *
     * @return static Returns an instance of the current object.
     */
    public function preProcessing(): static
    {
        $this->setFileExtension($this->getFile())->setFileOriginalName($this->getFile())->setFileName();

        return $this;
    }

    /**
     * Set the original name of the file.
     *
     * @param  string|UploadedFile  $file  The file or uploaded file.
     * @return static Returns an instance of the current object.
     */
    public function setFileOriginalName(string|UploadedFile $file): static
    {
        $this->originName = match (true)
        {
            $file instanceof UploadedFile => $this->getFile()->getClientOriginalName(),
            default => basename($file),
        };

        return $this;
    }

    /**
     * Get the original name of the file.
     *
     * @return string The original name of the file.
     */
    public function getFileOriginalName(): string
    {
        return $this->originName;
    }

    /**
     * Sets the file to be used for generating the thumbnail.
     *
     * This method also sets the file name, origin name, and file extension based on the given file.
     *
     * @param  string|UploadedFile  $file  The file to be used for generating the thumbnail
     * @throws FileNotFound|FileUploadNotSuccess If the file does not exist on the filesystem
     */
    public function setFile(string|UploadedFile $file): static
    {
        $fileFromRequest = true;
        if (empty($file))
        {
            throw FileNotFound::make();
        }

        if (is_string($file))
        {
            $fileFromRequest = false;
            if ( ! file_exists($file))
            {
                throw FileNotFound::make();
            }
            $fileName = pathinfo($file, PATHINFO_BASENAME);
            $file     = new UploadedFile($file, $fileName);
        }

        if ( ! ($file instanceof UploadedFile))
        {
            throw FileNotFound::make();
        }

        if ($fileFromRequest && ! $file->isValid())
        {
            throw FileUploadNotSuccess::make();
        }

        $this->file = $file;

        $this->preProcessing();

        return $this;
    }

    /**
     * Returns the file name for the thumbnail.
     *
     * @return UploadedFile The file name for the thumbnail
     */
    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * Sets the height of the thumbnail.
     *
     * @param  int  $height  The height of the thumbnail
     */
    public function setHeight(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Returns the height of the thumbnail.
     *
     * @return int The height of the thumbnail
     */
    public function getHeight(): int
    {
        return $this->height ?? config('thumbnail.options.height');
    }

    /**
     * Sets the width of the thumbnail.
     *
     * @param  int  $width  The width of the thumbnail
     */
    public function setWidth(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Returns the width of the thumbnail.
     *
     * @return int The width of the thumbnail
     */
    public function getWidth(): int
    {
        return $this->width ?? config('thumbnail.options.width');
    }

    /**
     * Sets the width and height of the thumbnail.
     *
     * @param  int  $width  The width of the thumbnail
     * @param  int  $height  The height of the thumbnail
     */
    public function setSize(int $width, int $height): static
    {
        $this->setWidth($width);
        $this->setHeight($height);
        return $this;
    }

    /**
     * Sets the layer method to use when merging image layers.
     *
     * @param  int  $layer  The layer method to use when merging image layers
     */
    public function setLayer(int $layer): static
    {
        $this->layer = $layer;
        return $this;
    }

    /**
     * Returns the layer method to use when merging image layers.
     *
     * @return int The layer method to use when merging image layers
     */
    public function getLayer(): int
    {
        return $this->layer ?? config('thumbnail.options.layer');
    }

    /**
     * Sets the ignore list for the thumbnail.
     *
     * @param  array  $ignore  The ignore list for the thumbnail
     */
    public function setIgnore(array $ignore): static
    {
        $this->ignore = $ignore;
        return $this;
    }

    /**
     * Returns the ignore list for the thumbnail.
     *
     * @return array The ignore list for the thumbnail
     */
    public function getIgnore(): array
    {
        return array_merge($this->ignore, config('thumbnail.ignore_extensions'));
    }

    /**
     * Sets the file extension for the thumbnail.
     *
     * @return Thumbnail
     */
    protected function setFileExtension($file): static
    {
        $this->fileExtension = match (true)
        {
            $file instanceof UploadedFile => strtolower($this->getFile()->getClientOriginalExtension()),
            default => pathinfo($file, PATHINFO_EXTENSION),
        };

        return $this;
    }

    /**
     * Returns the extension of the file.
     *
     * @return string The extension of the file
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * Sets the output format for the thumbnail.
     *
     * @param  string  $format  The output format for the thumbnail
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Returns the output format for the thumbnail.
     *
     * @return string The output format for the thumbnail
     * @throws FileFormatInvalid
     */
    public function getFormat(): string
    {
        $format = $this->format ?? config('thumbnail.options.format');
        $this->validateOutputFormat($format);
        return $format;
    }

    /**
     * Sets the options for generating the thumbnail.
     *
     * @param  array  $options  The options for generating the thumbnail
     * @return Thumbnail
     */
    public function setOptions(array $options): static
    {
        $options = Arr::only($options, array_keys(config('thumbnail.options')));
        collect($options)->each(function ($value, $option)
        {
            $method = 'set'.$option;
            if (method_exists($this, $method))
            {
                call_user_func([$this, $method], $value);
            }
        });
        return $this;
    }

    /**
     * Returns the options for generating the thumbnail.
     *
     * @return array The options for generating the thumbnail
     */
    public function getOptions(): array
    {
        $options = array_keys(config('thumbnail.options'));
        return collect($options)->map(function ($option)
        {
            $method = 'get'.$option;
            if (is_callable([$this, $method]))
            {
                return [$option => $this->$method()];
            }
            return null;
        })->collapse()->all();
    }

    /**
     * Sets the file name for the thumbnail.
     *
     * @return Thumbnail
     */
    protected function setFileName(): static
    {
        $this->originName = $this->getFile()->getClientOriginalName();

        $fileName = Str::of(pathinfo($this->getFileOriginalName(), PATHINFO_FILENAME))->append('_')->append(uniqid())->append('.')->append($this->getFileExtension())->snake();

        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Returns the file name for the thumbnail.
     *
     * @return string The file name for the thumbnail
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Returns the default thumbnail path.
     *
     * @return array An array containing the default thumbnail name and path
     */
    protected function getDefaultPath(): array
    {
        if (config('thumbnail.default.enable'))
        {
            return [
                'name' => $this->getFileName(),
                'original_name' => $this->getFileOriginalName(),
                'path' => config('thumbnail.default.path')
            ];
        }
        return [];
    }

    /**
     * If the format is not in the array of valid formats, throw an exception.
     *
     * @param  string  $format  The format of the file.
     *
     * @return bool A boolean value.
     * @throws FileFormatInvalid
     */
    protected function validateOutputFormat(string $format): bool
    {
        $validFormat = ['jpg', 'jpeg', 'png', 'gif'];
        if ( ! in_array($format, $validFormat))
        {
            throw FileFormatInvalid::make();
        }
        return true;
    }

    /**
     * Set the flag to indicate that the temporary file should not be removed.
     *
     * @return static Returns an instance of the current object.
     */
    protected function doNotRemoveTempFile(): static
    {
        $this->shouldRemoveTempFile = false;
        return $this;
    }
}
