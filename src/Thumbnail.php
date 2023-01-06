<?php

namespace PhuocNguyen\Thumbnail;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use PhuocNguyen\Thumbnail\Exception\ConvertToPdf;
use PhuocNguyen\Thumbnail\Exception\FileFormatInvalid;
use PhuocNguyen\Thumbnail\Exception\FileNotFound;

class Thumbnail
{
    protected string|UploadedFile $file;

    protected string $fileName;

    protected string $fileExtension = '';

    protected int $height;

    protected int $width;

    protected string $format;

    protected int $layer;

    protected bool $shouldRemoveTempFile = true;

    protected array $ignore = [];

    protected Imagick $imagick;

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

    public function __construct(Imagick $imagick)
    {
        $this->imagick = $imagick;
    }

    /**
     * @throws \ImagickException
     * @throws \Exception
     * @throws \Throwable
     */
    public function create($file = null): string
    {
        if (!empty($file)) {
            $this->setFile($file);
        }

        if (!$this->isFileCanGenerateThumbnail()) {
            return $this->getDefaultPath();
        }

        $tempPath = $this->cloneFileToTempDir($this->file);
        $this->pretreatmentOfficeFile($tempPath);

        return $this->processing($tempPath);
    }

    /**
     * @throws \ImagickException|FileFormatInvalid
     */
    protected function processing($tempPath): string
    {
        $fileName = Str::of(pathinfo($tempPath, PATHINFO_FILENAME))
            ->beforeLast('_')
            ->append('_thumbnail_')
            ->append(uniqid())
            ->append('.')
            ->append($this->getFormat())
            ->snake();

        $fileOutPutDir = config('filesystems.disks.thumbnail.root');
        $this->createDirectory();
        $fileOutPut = $fileOutPutDir . '/' . $fileName;

        $this->imagick->readImage($tempPath);
        $this->imagick->thumbnailImage($this->getWidth(), $this->getHeight());
        $this->imagick->setFormat($this->getFormat());
        $im = $this->imagick->mergeImageLayers(14);

        if ($im->writeImage($fileOutPut) === false) {
            return $this->getDefaultPath();
        }

        if ($this->shouldRemoveTempFile) {
            $this->removeFile($tempPath);
        }

        return Storage::disk('thumbnail')->url($fileName);
    }

    /**
     * @throws ConvertToPdf
     */
    protected function convertMsOfficeToPdf($tempPath): array|string
    {
        if ($this->isExcelFile()) {
            $cmd = "/usr/bin/libreoffice --headless --nologo --nofirststartwizard --norestore $tempPath  macro:///Standard.Module1.FitToPage";
            shell_exec($cmd);
        }
        $output = substr_replace($tempPath, 'pdf', strrpos($tempPath, '.') + 1);

        shell_exec("unoconv -f pdf -e PageRange=1-1 $tempPath --output=$output");

        if (file_exists($output)) {
            $this->removeFile($tempPath);
            return $output;
        }

        throw new ConvertToPdf('Cannot Convert MsOffice To PDF.');
    }

    protected function isFileCanGenerateThumbnail(): bool
    {
        $extensionCompare = array_diff($this->validExtensions, $this->getIgnore());
        return in_array($this->getFileExtension(), $extensionCompare, true);
    }

    protected function createDirectory($externalPath = '/', $disk = 'thumbnail'): bool
    {
        return Storage::disk($disk)->makeDirectory($externalPath);
    }

    protected function removeFile($fileName, $disk = 'temp_thumbnail'): bool
    {
        $fileName = pathinfo($fileName, PATHINFO_BASENAME);
        return Storage::disk($disk)->delete($fileName);
    }

    protected function cloneFileToTempDir($file): string
    {
        if (empty($file)) {
            throw FileNotFound::make();
        }

        Storage::disk('temp_thumbnail')->putFileAs('/', $this->file, $this->getFileName());

        return Storage::disk('temp_thumbnail')->path($this->getFileName());
    }

    /**
     * @throws ConvertToPdf
     */
    protected function pretreatmentOfficeFile(&$tempPath)
    {
        $officeExtensions = ['doc', 'docx', 'xls', 'xlsx'];
        if (in_array($this->getFileExtension(), $officeExtensions)) {
            $tempPath = $this->convertMsOfficeToPdf($tempPath);
        }

        return $tempPath;
    }

    protected function isExcelFile(): bool
    {
        return in_array($this->getFileExtension(), ['xls', 'xlsx']);
    }

    /**
     * @throws FileNotFound
     */
    public function setFile($file): static
    {
        if (empty($file)) {
            throw FileNotFound::make();
        }

        if (is_string($file)) {
            $fileName = pathinfo($file, PATHINFO_BASENAME);
            $file = new UploadedFile($file, $fileName);
        }

        if (!($file instanceof UploadedFile)) {
            throw FileNotFound::make();
        }

        $this->file = $file;

        $this->setFileExtension();
        $this->setFileName();

        return $this;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function setLayer(int $layer): static
    {
        $this->layer = $layer;
        return $this;
    }

    public function getLayer(): int
    {
        return $this->layer ?? config('thumbnail.thumbnail_layer');
    }

    public function setIgnore($ignore): static
    {
        $this->ignore = Arr::wrap($ignore);
        return $this;
    }

    public function getIgnore(): array
    {
        return array_merge($this->ignore, config('thumbnail.ignore_extension'));
    }

    public function setFileExtension(): static
    {
        $this->fileExtension = strtolower($this->file->getClientOriginalExtension());
        return $this;
    }

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    public function getFile(): string|UploadedFile
    {
        return $this->file;
    }

    public function setFileName(): static
    {
        $fileOriginalName = $this->getFile()->getClientOriginalName();
        $fileName = Str::of(pathinfo($fileOriginalName, PATHINFO_FILENAME))
            ->append('_')
            ->append(uniqid())
            ->append('.')
            ->append($this->getFile()->getClientOriginalExtension())
            ->snake();

        $this->fileName = $fileName;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getDefaultPath(): string
    {
        if (config('thumbnail.default_thumbnail')) {
            return config('thumbnail.default_path');
        }
        return '';
    }

    public function getHeight(): int
    {
        return $this->height ?? config('thumbnail.thumbnail_height');
    }

    public function getWidth(): int
    {
        return $this->width ?? config('thumbnail.thumbnail_width');
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @throws FileFormatInvalid
     */
    public function getFormat(): string
    {
        $format = $this->format ?? config('thumbnail.thumbnail_format');
        return $this->checkFormat($format);
    }

    /**
     * @throws FileFormatInvalid
     */
    protected function checkFormat(string $format): string
    {
        $validFormat = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($format, $validFormat)) {
            throw FileFormatInvalid::make();
        }
        return $format;
    }
}
