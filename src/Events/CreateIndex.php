<?php

namespace tp5er\think\scout\Events;

use think\Model;
use tp5er\think\scout\DataSync;

/**
 * Class CreateIndex
 * @author zhiqiang
 * @package tp5er\think\scout\Events
 */
class CreateIndex
{
    /**
     * @var array
     */
    public static $options = [];

    /**
     * @param Model $model
     */
    public function handle(Model $model)
    {
        (new DataSync($model))->createIndex(static::$options);
    }
}