<?php

namespace PhuocNguyen\Thumbnail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use PhuocNguyen\Thumbnail\Facade\Thumbnail as ThumbnailInstance;
use PhuocNguyen\Thumbnail\Models\Thumbnail;

trait HasThumbnail
{
    public static function bootHasThumbnail(): void
    {
        collect(static::eventsToCreatedThumbnail())->each(function ($event) {
            return static::$event(function (Model $model) {
                if (!static::shouldCreateThumbnail()) {
                    return false;
                }

                $files = request()->allFiles();
                if (!empty($files)) {
                    collect($files)
                        ->each(function ($file) use ($model) {
                            static::saveThumbnail($model, $file);
                        });
                }

                return true;
            });
        });
    }

    protected static function eventsToCreatedThumbnail(): array
    {
        if (isset(static::$thumbnailEvents)) {
            return static::$thumbnailEvents;
        }
        return ['saved', 'updated'];
    }

    protected static function shouldCreateThumbnail(): bool
    {
        if (isset(static::$doNotCreateThumbnail) && static::$doNotCreateThumbnail) {
            return false;
        }
        return true;
    }

    protected static function saveThumbnail($model, $file)
    {
        $thumbnail = ThumbnailInstance::setFile($file);

        if (isset(static::$thumbnailOptions)) {
            $thumbnail = $thumbnail->setOptions(static::$thumbnailOptions);
        }

        $thumbnail = $thumbnail->create();
        if (!empty($thumbnail)) {
            $saveData = static::getSaveData($thumbnail, $file, $model);

            if (isset(static::$thumbnailUpdateWillOverwrite) && static::$thumbnailUpdateWillOverwrite) {
                $latestThumbnail = $model->latestThumbnail;
                if ($latestThumbnail) {
                    return $latestThumbnail->fill($saveData)->save();
                }
            }

            if (method_exists(static::class, 'thumbnailCustomSave')) {
                return static::thumbnailCustomSave($thumbnail, $file, $model);
            }

            return $model->thumbnails()->create($saveData);
        }
        return true;
    }

    public static function getSaveData($thumbnail, $file, $model): array
    {
        $data = [
            'name' => $thumbnail['name'],
            'original_name' => $thumbnail['original_name'],
            'path' => $thumbnail['path']
        ];

        if (method_exists(static::class, 'thumbnailSaveData')) {
            $customData = static::thumbnailSaveData($thumbnail, $file, $model);
            $data = array_merge($data, $customData);
        }

        return $data;
    }

    public function thumbnails(): MorphMany
    {
        return $this->morphMany(Thumbnail::class, 'thumbnailable');
    }

    public function latestThumbnail(): MorphOne
    {
        return $this->morphOne(Thumbnail::class, 'thumbnailable')->latestOfMany();
    }
}
