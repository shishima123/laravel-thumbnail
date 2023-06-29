<?php

namespace Shishima\Thumbnail\Exception;

use Exception;

class FileUploadNotSuccess extends Exception
{
    public static function make($message = null): static
    {
        if (empty($message)) {
            $message = "File Upload Not Successfully!";
        }
        return new static($message);
    }
}
