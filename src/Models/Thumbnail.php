<?php

namespace Shishima\Thumbnail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Schema;

class Thumbnail extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('thumbnail.table_name'));
        parent::__construct($attributes);
        $this->fillable = Schema::getColumnListing($this->getTable());
    }

    public function thumbnailable(): MorphTo
    {
        return $this->morphTo();
    }
}
