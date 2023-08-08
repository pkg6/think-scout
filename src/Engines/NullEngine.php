<?php

namespace tp5er\think\scout\Engines;

use think\Collection;
use think\Model;
use tp5er\think\scout\Builder;
use tp5er\think\scout\Engine;

/**
 * Class NullEngine
 * @author zhiqiang
 * @package tp5er\think\scout\Engines
 */
class NullEngine extends Engine
{
    /**
     * Update the given model in the index.
     * @param Collection $models
     * @return void
     */
    public function update(Collection $models): void
    {
        // TODO: Implement update() method.
    }

    /**
     * Remove the given model from the index.
     * @param Collection $models
     * @return void
     */
    public function delete(Collection $models): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * Perform the given search on the engine.
     * @param Builder $builder
     * @return array
     */
    public function search(Builder $builder)
    {
        return [];
    }

    /**
     * Perform the given search on the engine.
     * @param Builder $builder
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function paginate(Builder $builder, int $perPage, int $page)
    {
        return [];
    }

    /**
     * Pluck and return the primary keys of the given results.
     * @param mixed $results
     * @return Collection
     */
    public function mapIds($results): Collection
    {
        return Collection::make();
    }

    /**
     * Map the given results to instances of the given model.
     * @param Builder $builder
     * @param mixed $results
     * @param Model $model
     * @return Collection
     */
    public function map(Builder $builder, $results, Model $model): Collection
    {
        return Collection::make();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     * @param mixed $results
     * @return int
     */
    public function getTotalCount($results): int
    {
        return count($results);
    }

    /**
     * Flush all of the model's records from the engine.
     * @param Model $model
     */
    public function flush(Model $model): void
    {

    }

    /**
     * Create a search index.
     * @param $name
     * @param array $options
     * @return mixed
     */
    public function createIndex($name, array $options = [])
    {
        return [];
    }

    /**
     * Delete a search index.
     *
     * @param string $name
     * @return mixed
     */
    public function deleteIndex($name)
    {
        return [];
    }

}