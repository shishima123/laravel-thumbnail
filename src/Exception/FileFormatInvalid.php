<?php

namespace Shishima\Thumbnail\Exception;

use Exception;

class FileFormatInvalid extends Exception
{
    public static function make($message = null): static
    {
        if (empty($message))
        {
            $message = "Incorrect file format, supported file formats are: jpg, jpeg, png, gif.";
        }
        return new static($message);
    }
}
