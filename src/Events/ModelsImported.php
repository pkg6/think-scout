<?php

namespace tp5er\think\scout\Events;

use think\Model;
use tp5er\think\scout\DataSync;


class ModelsImported
{
    /**
     * @var int
     */
    public static $chunk = 20;

    /**
     * @param Model $model
     */
    public function handle(Model $model)
    {
        (new DataSync($model))->chunkImported(static::$chunk);
    }
}