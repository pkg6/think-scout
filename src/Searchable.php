<?php

namespace tp5er\think\scout;

use Closure;
use think\Container;
use think\db\Query;
use tp5er\think\scout\Support\ModelHelp;



trait Searchable
{
    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * @param string $query
     * @param Closure $callback
     * @return Builder
     */
    public static function search($query = '', Closure $callback = null)
    {
        return Container::getInstance()->invokeClass(Builder::class, [
            'model'      => new static(),
            'query'      => $query,
            'callback'   => $callback,
            'softDelete' => static::usesSoftDelete() && config('scout.soft_delete', false),
        ]);
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return true;
    }


    /**
     * Get the indexable data array for the model.
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->toArray();
    }

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    public function getScoutKeyName()
    {
        return $this->getPk();
    }

    /**
     * Get the value used to index the model.
     *
     * @return mixed
     */
    public function getScoutKey()
    {
        return $this->getKey();
    }

    /**
     * @param \think\db\Builder $query
     * @return \think\db\Builder
     */
    protected function makeAllSearchableUsing($query)
    {
        return $query;
    }

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param int $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        return $this;
    }


    /**
     * @return Query
     */
    public function withoutTrashed()
    {
        $model = new static();
        return $model->db();
    }

    /**
     * @return Engine
     */
    public function searchableUsing()
    {
        return app()->get(Engine::class)->engine();
    }

    /**
     * @return bool
     */
    public static function usesSoftDelete()
    {
        return ModelHelp::isSoftDelete(get_called_class());
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return config('scout.prefix') . $this->getTable();
    }
}