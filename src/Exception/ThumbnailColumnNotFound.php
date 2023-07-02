<?php

namespace Shishima\Thumbnail\Exception;

use Exception;

class ThumbnailColumnNotFound extends Exception
{
    public static function make($message = null): static
    {
        if (empty($message))
        {
            $message = "Thumbnail Event Trigger Column not found!";
        }
        return new static($message);
    }
}
