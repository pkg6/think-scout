<?php

namespace tp5er\think\scout\Engines;

use think\Collection;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\helper\Arr;
use think\Model;
use tp5er\think\scout\Builder;
use tp5er\think\scout\Engine;
use tp5er\think\scout\Support\ModelHelp;

class CollectionEngine extends Engine
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
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function search(Builder $builder)
    {
        $models = $this->searchModels($builder);
        return [
            'results' => $models->all(),
            'total'   => count($models),
        ];
    }

    /**
     * Perform the given search on the engine.
     * @param Builder $builder
     * @param int $perPage
     * @param int $page
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function paginate(Builder $builder, int $perPage, int $page)
    {
        $models = $this->searchModels($builder);
        $offset = max(0, ($page - 1) * $perPage);
        return [
            'results' => $models->slice($offset, $perPage)->all(),
            'total'   => count($models),
        ];
    }

    /**
     * Pluck and return the primary keys of the given results.
     * @param mixed $results
     * @return Collection
     */
    public function mapIds($results): Collection
    {
        $results = $results['results'];
        return count($results) > 0 ?
            collect(Arr::pluck($results, $results[0]->getPk()))->values()
            : collect();
    }

    /**
     * Map the given results to instances of the given model.
     * @param Builder $builder
     * @param mixed $results
     * @param Model $model
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function map(Builder $builder, $results, Model $model): Collection
    {
        $results = $results['results'];
        if (count($results) === 0) {
            return collect($model);
        }
        $objectIds = Collection::make(Arr::pluck($results, $model->getPk()))->values()->all();
        return $this->getScoutModelsByIds($builder, $objectIds);
    }

    /**
     * Get the requested models from an array of object IDs.
     *
     * @param Builder $builder
     * @param array $ids
     * @return \think\model\Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getScoutModelsByIds(Builder $builder, array $ids)
    {
        $query = $builder->model->usesSoftDelete() ? $builder->model->withTrashed() : $builder->model->newQuery();
        if ($builder->queryCallback) {
            call_user_func($builder->queryCallback, $query);
        }
        return $query->whereIn(
            $builder->model->getPk(), $ids
        )->select();
    }


    /**
     * Get the total count from a raw result returned by the engine.
     * @param mixed $results
     * @return int
     */
    public function getTotalCount($results): int
    {
        return 0;
    }

    /**
     * Flush all of the model's records from the engine.
     * @param Model $model
     */
    public function flush(Model $model): void
    {
        // TODO: Implement flush() method.
    }


    /**
     * @param Builder $builder
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function searchModels(Builder $builder)
    {
        $query = $this->ensureSoftDeletesAreHandled($builder);

        $model = $query->when(!is_null($builder->callback), function (BaseQuery $query) use (&$builder) {
            call_user_func($builder->callback, $query, $builder, $builder->query);
        })->when(!$builder->callback && count($builder->wheres) > 0, function (BaseQuery $query) use ($builder) {
            foreach ($builder->wheres as $key => $value) {
                if ($key !== '__soft_deleted') {
                    $query->where($key, $value);
                }
            }
        })->when(!$builder->callback && count($builder->whereIns) > 0, function (BaseQuery $query) use ($builder) {
            foreach ($builder->whereIns as $key => $values) {
                $query->whereIn($key, $values);
            }
        })->order($builder->model->getPk(), 'desc');
        return $model->select()->filter(function (Model $model) use ($builder) {
            if (!$builder->model->shouldBeSearchable()) {
                return false;
            }
            if (!$builder->query) {
                return true;
            }
            return false;
        })->values();
    }


    /**
     * @param Builder $builder
     * @return Query | BaseQuery
     */
    protected function ensureSoftDeletesAreHandled(Builder $builder)
    {
        if (Arr::get($builder->wheres, '__soft_deleted') === 0) {
            return $builder->model->withoutTrashed();
        } elseif (Arr::get($builder->wheres, '__soft_deleted') === 1) {
            return $builder->model->onlyTrashed();
        } elseif (ModelHelp::isSoftDelete($builder->model) && config('scout.soft_delete', false)) {
            return $builder->model->withTrashed();
        }
        return $builder->model->db();
    }
}