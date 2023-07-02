<?php

namespace Shishima\Thumbnail\Exception;

use Exception;

class FileNotFound extends Exception
{
    public static function make($message = null): static
    {
        if (empty($message))
        {
            $message = "Invalid file input.";
        }
        return new static($message);
    }
}
