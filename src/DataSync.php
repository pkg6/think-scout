<?php

namespace tp5er\think\scout;

use think\Collection;
use think\facade\Config;
use think\Model;


class DataSync
{
    /**
     * @var Collection|Model
     */
    protected $models;

    /**
     * @var int
     */
    protected $chunk = 20;

    /**
     * @param Model|\think\model\Collection $models
     */
    public function __construct($models)
    {
        if (empty($models)) {
            return;
        }
        if ($models instanceof Model) {
            //数据为空的时候我认为他是单纯的模型，要做全量处理
            if (empty($models->toArray())) {
                $this->models = $models;
            } else {
                $tempModel[]  = $models;
                $this->models = Collection::make($tempModel);
            }
        }
        if ($models instanceof \think\model\Collection) {
            $this->models = $models;
        }
    }

    /**
     * 创建索引
     * @param array $options
     * @return void
     */
    public function createIndex(array $options = [])
    {
        $this->models->searchableUsing()->createIndex($this->models->searchableAs(), $options);
    }

    /**
     * 删除索引
     * @return void
     */
    public function deleteIndex()
    {
        $this->models->searchableUsing()->deleteIndex($this->models->searchableAs());
    }


    /**
     * 增量更新
     * @param int $chunk
     * @return void
     */
    public function chunkUpdated(int $chunk = 0)
    {
        $chunk = $chunk ?: Config::get('scout.chunk', $this->chunk);
        $this->models->chunk($chunk)->each(function (Collection $model) {
            $first = $model->first();
            if ($first->shouldBeSearchable()) {
                $first->searchableUsing()->update($this->models);
            }
        });
    }


    /**
     * 分块删除数据
     * @param int $chunk
     * @return void
     */
    public function chunkDeleted(int $chunk = 0)
    {
        $chunk = $chunk ?: Config::get('scout.chunk', $this->chunk);
        $this->models->chunk($chunk)->each(function (Collection $model) {
            $first = $model->first();
            if ($first->shouldBeSearchable()) {
                $first->searchableUsing()->delete($this->models);
            }
        });
    }


    /**
     * 全量导入
     * @param int $chunk
     * @return void
     */
    public function chunkImported(int $chunk = 0)
    {
        $soft  = $this->models::usesSoftDelete() && config('scout.soft_delete', false);
        $query = $soft ? $this->models->withTrashed() : $this->models->newQuery();
        $chunk = $chunk ?: Config::get('scout.chunk', $this->chunk);
        $query->chunk($chunk, function (\think\model\Collection $models) {
            $first = $models->first();
            if ($first->shouldBeSearchable()) {
                $first->searchableUsing()->update($models);
            }
        });
    }

    /**
     * 全量清空搜索引擎中的数据
     * @return void
     */
    public function flushed()
    {
        $this->models->searchableUsing()->flush($this->models);
    }
}