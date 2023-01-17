<?php

namespace Shishima\Thumbnail\Facade;

use Illuminate\Support\Facades\Facade;
use Shishima\Thumbnail\Thumbnail as ThumbnailInstance;

class Thumbnail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ThumbnailInstance::class;
    }
}
