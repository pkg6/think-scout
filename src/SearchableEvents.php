<?php

namespace tp5er\think\scout;

use think\facade\Event;
use think\Model;
use tp5er\think\scout\Support\ModelHelp;


trait SearchableEvents
{

    /**
     * 写入后
     * @param Model $model
     */
    public static function onAfterWrite(Model $model)
    {
        ModelHelp::isSearchable(get_called_class()) && Event::trigger('onScoutUpdated', $model);
    }

    /**
     * 新增后
     * @param Model $model
     */
    public static function onAfterInsert(Model $model)
    {
        ModelHelp::isSearchable(get_called_class()) && Event::trigger('onScoutUpdated', $model);
    }

    /**
     * 更新后
     * @param Model $model
     */
    public static function onAfterUpdate(Model $model)
    {
        ModelHelp::isSearchable(get_called_class()) && Event::trigger('onScoutUpdated', $model);
    }

    /**
     * 删除后
     * @param Model $model
     */
    public static function onAfterDelete(Model $model)
    {
        ModelHelp::isSearchable(get_called_class()) && Event::trigger('onScoutDeleted', $model);
    }
}