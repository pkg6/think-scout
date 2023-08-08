<?php

namespace tp5er\think\scout;

use think\Collection;
use think\Model;


abstract class Engine
{

    /**
     * @var array
     */
    protected $config;

    /**
     * Engine constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * Create a search index.
     * @param $name
     * @param array $options
     * @return void
     */
    public function createIndex($name, array $options = [])
    {
    }

    /**
     * Delete a search index.
     *
     * @param string $name
     * @return void
     */
    public function deleteIndex($name)
    {
    }

    /**
     * Update the given model in the index.
     * @param Collection $models
     * @return void
     */
    abstract public function update(Collection $models): void;

    /**
     * Remove the given model from the index.
     * @param Collection $models
     * @return void
     */
    abstract public function delete(Collection $models): void;

    /**
     * Perform the given search on the engine.
     * @param Builder $builder
     * @return array
     */
    abstract public function search(Builder $builder);

    /**
     * Perform the given search on the engine.
     * @param Builder $builder
     * @param int $perPage
     * @param int $page
     * @return array
     */
    abstract public function paginate(Builder $builder, int $perPage, int $page);

    /**
     * Pluck and return the primary keys of the given results.
     * @param mixed $results
     * @return Collection
     */
    abstract public function mapIds($results): Collection;

    /**
     * Map the given results to instances of the given model.
     * @param Builder $builder
     * @param mixed $results
     * @param Model $model
     * @return Collection
     */
    abstract public function map(Builder $builder, $results, Model $model): Collection;

    /**
     * Get the total count from a raw result returned by the engine.
     * @param mixed $results
     * @return int
     */
    abstract public function getTotalCount($results): int;

    /**
     * Flush all of the model's records from the engine.
     * @param Model $model
     * @return void
     */
    abstract public function flush(Model $model): void;
    

    /**
     * Pluck and return the primary keys of the given results using the given key name.
     *
     * @param mixed $results
     * @return Collection
     */
    public function mapIdsFrom($results)
    {
        return $this->mapIds($results);
    }

    /**
     * Get the results of the query as a Collection of primary keys.
     * @param Builder $builder
     * @return Collection
     */
    public function keys(Builder $builder): Collection
    {
        return $this->mapIds($this->search($builder));
    }

    /**
     * Get the results of the given query mapped onto models.
     * @param Builder $builder
     * @return Collection
     */
    public function get(Builder $builder): Collection
    {
        return $this->map(
            $builder,
            $this->search($builder),
            $builder->model
        );
    }

}