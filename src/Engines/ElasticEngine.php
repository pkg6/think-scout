<?php

namespace tp5er\think\scout\Engines;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use stdClass;
use think\Collection;
use think\helper\Arr;
use think\Model;
use tp5er\think\scout\Builder;
use tp5er\think\scout\Engine;

/**
 * Class ElasticEngine
 * @author zhiqiang
 * @package tp5er\think\scout\Engines
 */
class ElasticEngine extends Engine
{

    /**
     * elastic Correspondence between parameters and functions
     * @var array
     */
    public static $configMappings = [
        'sslVerification'    => 'setSSLVerification',
        'sniffOnStart'       => 'setSniffOnStart',
        'retries'            => 'setRetries',
        'httpHandler'        => 'setHandler',
        'connectionPool'     => 'setConnectionPool',
        'connectionSelector' => 'setSelector',
        'serializer'         => 'setSerializer',
        'connectionFactory'  => 'setConnectionFactory',
        'endpoint'           => 'setEndpoint',
        'namespaces'         => 'registerNamespace',
    ];

    /**
     * @return Client
     */
    public function elastic()
    {
        $config = $this->config;
        if (!class_exists(ClientBuilder::class)) {
            throw new \RuntimeException("Please Install elasticsearch/elasticsearch Package");
        }
        $clientBuilder = ClientBuilder::create();
        $clientBuilder->setHosts($config['hosts']);
        // Set additional client configuration
        foreach (static::$configMappings as $key => $method) {
            $value = Arr::get($config, $key);
            if (is_array($value)) {
                foreach ($value as $vItem) {
                    $clientBuilder->$method($vItem);
                }
            } elseif ($value !== null) {
                $clientBuilder->$method($value);
            }
        }
        return $clientBuilder->build();
    }

    /**
     * Update the given model in the index.
     * @param Collection $models
     * @return void
     */
    public function update(Collection $models): void
    {
        if (empty($models)) {
            return;
        }
        $params['body'] = [];
        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'update' => [
                    '_id'    => $model->getKey(),
                    '_index' => $this->buildElasticIndex($model),
                    '_type'  => get_class($model),
                ]
            ];
            $params['body'][] = [
                'doc'           => $model->toSearchableArray(),
                'doc_as_upsert' => true
            ];
        });
        $this->elastic()->bulk($params);
    }

    /**
     * Remove the given model from the index.
     * @param Collection $models
     * @return void
     */
    public function delete(Collection $models): void
    {
        $params['body'] = [];
        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'delete' => [
                    '_id'    => $model->getKey(),
                    '_index' => $this->buildElasticIndex($model),
                    '_type'  => get_class($model),
                ]
            ];
        });
        $this->elastic()->bulk($params);
    }

    /**
     * Perform the given search on the engine.
     * @param Builder $builder
     * @return array
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'numericFilters' => $this->filters($builder),
            'size'           => $builder->limit,
        ]));
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
        $result            = $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'from'           => (($page * $perPage) - $perPage),
            'size'           => $perPage,
        ]);
        $result['nbPages'] = $this->getTotalCount($result) / $perPage;
        $result['total']   = $this->getTotalCount($result);
        return $result;
    }

    /**
     * Pluck and return the primary keys of the given results.
     * @param mixed $results
     * @return Collection
     */
    public function mapIds($results): Collection
    {
        return Collection::make(Arr::pluck($results['hits']['hits'], '_id'))->values();
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
        if ($this->getTotalCount($results) === 0) {
            return Collection::make($model);
        }
        $keys = $this->mapIds($results)->all();
        return Collection::make($results['hits']['hits'])->filter(function ($hits) use (&$builder, &$keys) {
            return in_array(Collection::make($hits['_source'])->offsetGet($builder->model->getPk()), $keys);
        })->values();
    }


    /**
     * Get the total count from a raw result returned by the engine.
     * @param mixed $results
     * @return int
     */
    public function getTotalCount($results): int
    {
        if (!empty($results['hits']['total'])) {
            return $results['hits']['total'];
        }
        if (!empty($results['hits']['total']['value'])) {
            return $results['hits']['total']['value'];
        }
        return 0;
    }

    /**
     * Flush all of the model's records from the engine.
     * @param Model $model
     * @return void
     */
    public function flush(Model $model): void
    {
        $params = [
            'index' => $this->buildElasticIndex($model),
            'type'  => get_class($model),
            'body'  => [
                'query' => [
                    "match_all" => new stdClass()
                ]
            ]
        ];
        $this->elastic()->deleteByQuery($params);
    }

    /**
     * @param Builder $builder
     * @param array $options
     * @return array|mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {

        $params = [
            'index' => $builder->index ?? $this->buildElasticIndex($builder->model),
            'type'  => get_class($builder->model),
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'query_string' => ['query' => "*{$builder->query}*"]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($sort = $this->sort($builder)) {
            $params['body']['sort'] = $sort;
        }

        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }
        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }

        if (isset($options['numericFilters']) && count($options['numericFilters'])) {
            $params['body']['query']['bool']['must'] = array_merge(
                $params['body']['query']['bool']['must'],
                $options['numericFilters']
            );
        }
        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elastic(),
                $builder->query,
                $params
            );
        }
        return $this->elastic()->search($params);
    }

    /**
     * Generates the sort if theres any.
     *
     * @param Builder $builder
     * @return array|null
     */
    protected function sort(Builder $builder)
    {
        if (count($builder->orders) == 0) {
            return null;
        }
        return Collection::make($builder->orders)->map(function ($order) {
            return [$order['column'] => $order['direction']];
        })->toArray();
    }

    /**
     * Get the filter array for the query.
     *
     * @param Builder $builder
     * @return array
     */
    protected function filters(Builder $builder)
    {
        return Collection::make($builder->wheres)->each(function ($value, $key) {
            if (is_array($value)) {
                return ['terms' => [$key => $value]];
            }
            return ['match_phrase' => [$key => $value]];
        })->values()->all();
    }


    /**
     * @param Model $model
     * @return string
     */
    protected function buildElasticIndex(Model $model)
    {
        return $model->searchableAs();
    }
}