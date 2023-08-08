<?php

namespace tp5er\think\scout\Support;

use think\model\concern\SoftDelete;
use tp5er\think\scout\Searchable;


class ModelHelp
{
    /**
     * @param $class
     * @return bool
     */
    public static function isSoftDelete($class)
    {
        return in_array(SoftDelete::class, class_uses_recursive($class));
    }

    /**
     * @param $class
     * @return bool
     */
    public static function isSearchable($class)
    {
        return in_array(Searchable::class, class_uses_recursive($class));
    }
}