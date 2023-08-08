前言

> [tp5er/think-scout](https://github.com/tp5er/think-scouts)根据thinkphp设计思想参考[laravel/scout](https://github.com/laravel/scout)进行扩展

tp5er/think-scout 为模型的全文搜索提供了一个简单的、基于驱动程序的解决方案。

目前，Scout 自带了一个 Elasticsearch 驱动；而编写自定义驱动程序很简单，你可以自由地使用自己的搜索实现来扩展 Scout。



## 命令行使用

~~~
//创建模型索引
php think scout:index "app\\model\\User"
//删除模型的索引
php think scout:delete-index "app\\model\\User"
//分批将模型中的数据同步到引擎中
php think scout:import "app\\model\\User"
//清空引擎中的数据（危险操作慎重使用）
php think scout:flush "app\\model\\User"
~~~

## Scout事件

| 事件               | 描述                  | 事件使用方法名                                    |
| ------------------ | --------------------- | ------------------------------------------------- |
| onScoutUpdated     | 注册模型新增/更新事件 | Event::trigger('onScoutUpdated', User::find(1));  |
| onScoutDeleted     | 注册模型删除事件      | Event::trigger('onScoutDeleted', User::find(1));  |
| onScoutImported    | 注册模型全量新增/更新 | Event::trigger('onScoutImported', new User());    |
| onScoutFlushed     | 注册模型全量删除数据  | Event::trigger('onScoutFlushed', new User());     |
| onScoutCreateIndex | 注册模型索引创建      | Event::trigger('onScoutCreateIndex', new User()); |
| onScoutDeleteIndex | 注册模型索引删除      | Event::trigger('onScoutDeleteIndex', new User()); |

## 模型事件

> 官网参考地址：https://www.kancloud.cn/manual/thinkphp6_0/1037598

| 事件           | 描述   | 事件方法名      |
| :------------- | :----- | :-------------- |
| after_read     | 查询后 | onAfterRead     |
| before_insert  | 新增前 | onBeforeInsert  |
| after_insert   | 新增后 | onAfterInsert   |
| before_update  | 更新前 | onBeforeUpdate  |
| after_update   | 更新后 | onAfterUpdate   |
| before_write   | 写入前 | onBeforeWrite   |
| after_write    | 写入后 | onAfterWrite    |
| before_delete  | 删除前 | onBeforeDelete  |
| after_delete   | 删除后 | onAfterDelete   |
| before_restore | 恢复前 | onBeforeRestore |
| after_restore  | 恢复后 | onAfterRestore  |

## 注册Scout事件到模型事件中

> 如果需要通过模型事件来自动完成Scout数据增量同步到引擎中需要进行手动注册
>
> 重要事情说三遍：
>
> 手动注册Scout事件到模型事件中！
>
> 手动注册Scout事件到模型事件中！
>
> 手动注册Scout事件到模型事件中！

### 手动依次注册

> 最佳的方式是使用队列

~~~
<?php
declare (strict_types = 1);
namespace app\model;
use think\Model;
use tp5er\think\scout\Searchable;
use think\facade\Event;
use tp5er\think\scout\DataSync;
/**
 * @mixin \think\Model
 */
class User extends Model
{
    use Searchable;
    
    public static function onAfterDelete(Model $model)
    {
       // Event::trigger('onScoutDeleted', $model);
       // 或
       // (new DataSync($model))->chunkDeleted(1);
       
    }

    public static function onAfterWrite(Model $model)
    {
       // Event::trigger('onScoutUpdated', $model);
       // 或
       // (new DataSync($model))->chunkUpdated(1);
    }
}
~~~

## 安装

引入组件包和 Elasticsearch 驱动

~~~
composer require tp5er/think-scout
// 根据自己的elasticsearch版本进行安装
composer require elasticsearch/elasticsearch
~~~

最后，在你要做搜索的模型中添加`tp5er\think\scout\Searchable` trait。这个 trait 会注册一个模型观察者来保持模型和所有驱动的同步：

```
<?php
declare (strict_types = 1);
namespace app\model;
use think\Model;
use tp5er\think\scout\Searchable;

/**
 * @mixin \think\Model
 */
class User extends Model
{
    use Searchable;
}
```

## 配置文件`config/scout.php`

~~~
<?php

return [
    /**
     * Default Search Engine
     * Supported:  "collection", "null" "elastic"
     */
    'default'     => env('SCOUT_DRIVER', 'collection'),
    //Soft Deletes
    'soft_delete' => false,
    //分块处理数据
    'chunk'       => 20,
    //engine Configuration
    'engine'      => [
        'collection' => [
            'driver' => \tp5er\think\scout\Engines\CollectionEngine::class,
        ],
        'null'       => [
            'driver' => \tp5er\think\scout\Engines\NullEngine::class,
        ],
        'elastic'    => [
            'driver' => \tp5er\think\scout\Engines\ElasticEngine::class,
            'prefix' => '',
            //https://www.elastic.co/guide/cn/elasticsearch/php/current/_configuration.html
            'hosts'  => [
                [
                    'host'   => 'localhost',
                    'port'   => "9200",
                    'scheme' => null,
                    'user'   => null,
                    'pass'   => null,
                ],
            ],
        ]
    ],
];
~~~

## 配置模型索引

每个模型与给定的搜索「索引」同步，这个「索引」包含该模型的所有可搜索记录。换句话说，你可以把每一个「索引」设想为一张 MySQL
数据表。默认情况下，每个模型都会被持久化到与模型的「表」名（通常是模型名称的复数形式）相匹配的索引。你也可以通过覆盖模型上的 searchableAs 方法来自定义模型的索引：

~~~
<?php
declare (strict_types = 1);
namespace app\model;
use think\Model;
use tp5er\think\scout\Searchable;

/**
 * @mixin \think\Model
 */
class User extends Model
{
    use Searchable;
    
    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return $this->getTable();
    }
}
~~~

## 暂停索引

~~~
<?php
declare (strict_types = 1);
namespace app\model;
use think\Model;
use tp5er\think\scout\Searchable;

/**
 * @mixin \think\Model
 */
class User extends Model
{
    use Searchable;
    
    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return true;
    }
}
~~~

## 搜索

你可以使用 search 方法来搜索模型。search 方法接受一个用于搜索模型的字符串。你还需在搜索查询上链式调用 get 方法，才能用给定的搜索语句查询与之匹配的模型模型：

~~~
User::search()->get();
~~~

如果你想在它们返回模型模型前得到原结果，你应该使用`raw` 方法:

```
User::search()->raw();
```

搜索查询通常会在模型的 searchableAs 方法指定的索引上执行。当然，你也可以使用 within 方法指定应该搜索的自定义索引:

~~~
User::search()->within('tp5er_user')->get();
~~~

### Where 语句

Scout 允许你在搜索查询中增加简单的「where」语句。目前，这些语句只支持基本的数值等式检查，并且主要是用于根据拥有者的 ID 进行的范围搜索查询。由于搜索索引不是关系型数据库，因此当前不支持更高级的「where」语句：

~~~
User::search()->where('user_id', 1)->get();
~~~

### 分页

除了检索模型的集合，你也可以使用 paginate 方法对搜索结果进行分页。这个方法会返回一个就像 传统的模型查询分页 一样的 Paginator 实例：

~~~
User::search()->paginate();
~~~

你可以通过将数量作为第一个参数传递给 paginate 方法来指定每页检索多少个模型：

~~~
User::search()->paginate(15);
~~~

## 自定义引擎

如果内置的 Scout 搜索引擎不能满足你的需求，你可以写自定义的引擎并且将它注册到 Scout。你的引擎需要继承 `tp5er\think\scout\Engine` 抽象类，这个抽象类包含了你自定义的引擎必须要实现的十种抽象方法：

~~~
abstract public function update(Collection $models): void;
abstract public function delete(Collection $models): void;
abstract public function search(Builder $builder);
abstract public function paginate(Builder $builder, int $perPage, int $page);
abstract public function mapIds($results): Collection;
abstract public function map(Builder $builder, $results, Model $model): Collection;
abstract public function getTotalCount($results): int;
abstract public function flush(Model $model): void;
~~~

### 注册引擎

一旦你写好了自定义引擎，您就可以在配置文件中指定引擎了。举个例子，如果你写好了一个 MySqlSearchEngine，您就可以在配置文件中这样写：

~~~
<?php

return [
    'default'     => env('SCOUT_DRIVER', 'mysql'),
    'soft_delete' => false,
    'engine'      => [
        'mysql'         => [
            'driver' => MySqlSearchEngine::class,
            'prefix' => '',
        ]
    ],
];
~~~
