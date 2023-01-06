<?php

namespace PhuocNguyen\Thumbnail\Facade;

use Illuminate\Support\Facades\Facade;
use PhuocNguyen\Thumbnail\Thumbnail as ThumbnailInstance;

class Thumbnail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ThumbnailInstance::class;
    }
}
