<?php

namespace tp5er\think\scout;

use Closure;
use think\Collection;
use think\Model;
use think\Paginator;
use tp5er\think\scout\Traits\Macroable;

class Builder
{
    use Macroable;

    /**
     * @var Model
     */
    public $model;
    /**
     * The query expression.
     *
     * @var string
     */
    public $query;

    /**
     * Optional callback before search execution.
     *
     * @var Closure|null
     */
    public $callback;

    /**
     * Optional callback before model query execution.
     *
     * @var |Closure|null
     */
    public $queryCallback;

    /**
     * The custom index specified for the search.
     *
     * @var string
     */
    public $index;

    /**
     * The "where" constraints added to the query.
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The "where in" constraints added to the query.
     *
     * @var array
     */
    public $whereIns = [];

    /**
     * The "limit" that should be applied to the search.
     * @var int
     */
    public $limit = 20;

    /**
     * The "order" that should be applied to the search.
     *
     * @var array
     */
    public $orders = [];

    /**
     * Create a new search builder instance.
     * @param Model $model
     * @param string $query
     * @param Closure|null $callback
     * @param bool $softDelete
     * @return void
     */
    public function __construct($model, $query, $callback = null, $softDelete = false)
    {
        $this->model    = $model;
        $this->query    = $query;
        $this->callback = $callback;
        if ($softDelete) {
            $this->wheres['__soft_deleted'] = 0;
        }
    }

    /**
     * Specify a custom index to perform this search on.
     *
     * @param string $index
     * @return $this
     */
    public function within($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * Add a constraint to the search query.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function where($field, $value)
    {
        $this->wheres[$field] = $value;

        return $this;
    }


    /**
     * Include soft deleted records in the results.
     *
     * @return $this
     */
    public function withTrashed()
    {
        unset($this->wheres['__soft_deleted']);
        return $this;
    }

    /**
     * Include only soft deleted records in the results.
     *
     * @return $this
     */
    public function onlyTrashed()
    {
        return tap($this->withTrashed(), function () {
            $this->wheres['__soft_deleted'] = 1;
        });
    }

    /**
     * Set the "limit" for the search query.
     *
     * @param int $limit
     * @return $this
     */
    public function take($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add an "order" for the search query.
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column'    => $column,
            'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ];
        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable $default
     * @return mixed
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }
        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @param Closure $callback
     * @return $this
     */
    public function tap($callback)
    {
        return $this->when(true, $callback);
    }

    /**
     * Set the callback that should have an opportunity to modify the database query.
     *
     * @param callable $callback
     * @return $this
     */
    public function query($callback)
    {
        $this->queryCallback = $callback;
        return $this;
    }

    /**
     * Get the raw results of the search.
     *
     * @return mixed
     */
    public function raw()
    {
        return $this->engine()->search($this);
    }

    /**
     * Get the keys of search results.
     *
     * @return Collection
     */
    public function keys()
    {
        return $this->engine()->keys($this);
    }

    /**
     * Get the first result from the search.
     * @return Collection
     */
    public function first()
    {
        return Collection::make($this->get()->first());
    }


    /**
     * Get the results of the search.
     * @return Collection
     */
    public function get()
    {
        return $this->engine()->get($this);
    }

    /**
     * @param int|null $perPage
     * @param string $pageName
     * @param null $page
     * @param bool $simple
     * @return Paginator
     */
    public function paginate(?int $perPage = null, $pageName = 'page', $page = null, $simple = false)
    {
        $engine  = $this->engine();
        $page    = $page ?: Paginator::getCurrentPage($pageName);
        $perPage = $perPage ?: $this->model->getPerPage();
        $rawResults = $engine->paginate($this, $perPage, $page);
        $results = $this->model->toCollection($engine->map(
            $this,
            $rawResults,
            $this->model
        )->all());
        $total   =$rawResults['total'];
        return Paginator::make($results, $perPage, $page, $total, $simple);
    }

    /**
     * Get the engine that should handle the query.
     *
     * @return Engine
     */
    public function engine()
    {
        return $this->model->searchableUsing();
    }

}