<?php

namespace tp5er\think\scout\Events;

use think\Model;
use tp5er\think\scout\DataSync;


class DeleteIndex
{
    /**
     * @param Model $model
     */
    public function handle(Model $model)
    {
        (new DataSync($model))->deleteIndex();
    }
}