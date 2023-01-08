<?php

namespace PhuocNguyen\Thumbnail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Thumbnail extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('thumbnail.table_name'));
        parent::__construct($attributes);
        $this->fillable = array_merge($this->fillable, config('thumbnail.table_fillable'));

    }

    public function thumbnailable(): MorphTo
    {
        return $this->morphTo();
    }
}
