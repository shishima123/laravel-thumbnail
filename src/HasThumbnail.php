<?php

namespace Shishima\Thumbnail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Shishima\Thumbnail\Facade\Thumbnail as ThumbnailInstance;

trait HasThumbnail
{
    /**
     * Registers model events for creating thumbnails.
     *
     * This method registers the `saved` and `updated` model events to create thumbnails when the model is saved or updated.
     * The model events to register can be customized by setting the `$thumbnailEvents` property on the model class.
     */
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

    /**
     * Returns the model events to register for creating thumbnails.
     *
     * This method returns the `saved` and `updated` model events by default, but these can be customized by setting the
     * `$thumbnailEvents` property on the model class.
     *
     * @return array The model events to register
     */
    protected static function eventsToCreatedThumbnail(): array
    {
        $events = ['saved', 'updated'];
        if (isset(static::$thumbnailEvents)) {
            return array_intersect($events, static::$thumbnailEvents);
        }
        return $events;
    }

    /**
     * Determines if thumbnails should be created when the model is saved or updated.
     *
     * This method returns `true` by default, but this can be customized by setting the `$doNotCreateThumbnail` property
     * on the model class to `true`.
     *
     * @return bool `true` if thumbnails should be created, `false` otherwise
     */
    protected static function shouldCreateThumbnail(): bool
    {
        if (isset(static::$doNotCreateThumbnail) && static::$doNotCreateThumbnail) {
            return false;
        }
        return true;
    }

    /**
     * Saves a thumbnail for the model.
     * This method creates a thumbnail from the given file using the `Thumbnail` class, and saves it to the database
     * as a `Thumbnail` model.
     *
     * @param $model // The model to save the thumbnail for
     * @param $file // The file to create the thumbnail from
     * @return bool|Model The saved thumbnail model, or `true` if the thumbnail was saved in a custom way
     */
    protected static function saveThumbnail($model, $file): Model|bool
    {
        $thumbnail = ThumbnailInstance::setFile($file);

        // If the `$thumbnailOptions` property on the model class is set,
        // custom options will be set separately
        if (isset(static::$thumbnailOptions)) {
            $thumbnail = $thumbnail->setOptions(static::$thumbnailOptions);
        }

        $thumbnail = $thumbnail->create();
        if (!empty($thumbnail)) {
            $saveData = static::getSaveData($thumbnail, $file, $model);

            // If the `$thumbnailUpdateWillOverwrite` property on the model class is set to `true`,
            // the latest thumbnail for the model will be overwritten instead of creating a new one.
            if (isset(static::$thumbnailUpdateWillOverwrite) && static::$thumbnailUpdateWillOverwrite) {
                $latestThumbnail = $model->latestThumbnail;
                if ($latestThumbnail) {
                    return $latestThumbnail->fill($saveData)->save();
                }
            }

            // If the model class has a `thumbnailCustomSave()` method,
            // that method will be called instead of saving the thumbnail to the database.
            if (method_exists(static::class, 'thumbnailCustomSave')) {
                return static::thumbnailCustomSave($thumbnail, $file, $model);
            }

            return $model->thumbnails()->create($saveData);
        }
        return true;
    }

    /**
     * Returns the data to be saved for the thumbnail.
     *
     * @param $thumbnail // The generated thumbnail data
     * @param $file // The file that was used to generate the thumbnail
     * @param $model // The model the thumbnail is being saved for
     * @return array The data to be saved for the thumbnail
     */
    public static function getSaveData($thumbnail, $file, $model): array
    {
        $data = [
            'name' => $thumbnail['name'],
            'original_name' => $thumbnail['original_name'],
            'path' => $thumbnail['path']
        ];

        // If the model class has a `thumbnailSaveData()` method,
        // that method will be called and return data to save to db
        if (method_exists(static::class, 'thumbnailSaveData')) {
            $customData = static::thumbnailSaveData($thumbnail, $file, $model);
            $data = array_merge($data, $customData);
        }

        return $data;
    }

    /**
     * Returns the thumbnails relationship for the model.
     *
     * @return MorphMany The thumbnails relationship for the model
     */
    public function thumbnails(): MorphMany
    {
        return $this->morphMany($this->getModel(), 'thumbnailable');
    }

    /**
     * Returns the latest thumbnail for the model.
     *
     * @return MorphOne The latest thumbnail for the model
     */
    public function latestThumbnail(): MorphOne
    {
        return $this->morphOne($this->getModel(), 'thumbnailable')->latestOfMany();
    }

    /**
     * Returns the model for save thumbnail to database
     *
     */
    protected function getModel()
    {
        return config('thumbnail.thumbnail_model');
    }
}
