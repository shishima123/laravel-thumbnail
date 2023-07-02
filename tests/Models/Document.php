<?php

namespace Shishima\Thumbnail\Tests\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Shishima\Thumbnail\HasThumbnail;

class Document extends Eloquent
{
    use HasThumbnail;

    protected $table = 'documents';

    protected $guarded = [];
}
