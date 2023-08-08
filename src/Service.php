<?php

namespace tp5er\think\scout;

use think\facade\Event;
use tp5er\think\scout\Commands\DeleteIndexCommand;
use tp5er\think\scout\Commands\FlushCommand;
use tp5er\think\scout\Commands\ImportCommand;
use tp5er\think\scout\Commands\IndexCommand;
use tp5er\think\scout\Events\CreateIndex;
use tp5er\think\scout\Events\DeleteIndex;
use tp5er\think\scout\Events\ModelDeleted;
use tp5er\think\scout\Events\ModelsFlushed;
use tp5er\think\scout\Events\ModelsImported;
use tp5er\think\scout\Events\ModelUpdated;


class Service extends \think\Service
{
    public function boot()
    {
        $this->commands([
            IndexCommand::class,
            DeleteIndexCommand::class,
            ImportCommand::class,
            FlushCommand::class,
        ]);
    }

    public function register()
    {
        // 注册模型新增/更新事件
        Event::listen('onScoutUpdated', ModelUpdated::class);
        // 注册模型删除事件
        Event::listen('onScoutDeleted', ModelDeleted::class);
        // 注册模型全量新增/更新
        Event::listen('onScoutImported', ModelsImported::class);
        // 注册模型全量删除数据
        Event::listen('onScoutFlushed', ModelsFlushed::class);
        //注册模型索引创建
        Event::listen('onScoutCreateIndex', CreateIndex::class);
        //注册模型索引删除
        Event::listen('onScoutDeleteIndex', DeleteIndex::class);

        $this->app->bind(Engine::class, function () {
            return new EngineManager($this->app);
        });
    }
}