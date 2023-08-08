<?php

namespace tp5er\think\scout\Events;

use think\Model;
use tp5er\think\scout\DataSync;

class ModelUpdated
{
    /**
     * @var int
     */
    public static $chunk = 20;

    /**
     * @param Model|\think\model\Collection $models
     */
    public function handle($models)
    {
        if (empty($models)) {
            return;
        }
        (new DataSync($models))->chunkUpdated(static::$chunk);
    }

}