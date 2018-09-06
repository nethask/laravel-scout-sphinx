<?php

namespace Egwk\LaravelScoutSphinx\Engine;

use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Foolz\SphinxQL\Drivers\ResultSetInterface;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as AbstractEngine;

class SphinxEngine extends AbstractEngine
{

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $hosts = [];

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $connections;

    public function __construct($hosts = [], array $options = [])
    {
        $connection = new Connection();
        $connection->setParams($hosts);
        $this->connections = $connection;
    }

    /**
     * @return array
     */
    public function getHosts()
    {
        return $this->hosts;
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     *
     * @return void
     */
    public function update($models)
    {
        if ($models->isEmpty())
        {
            return;
        }

        $example = $models->first();
        $index = $example->searchableAs();
        $columns = array_keys($example->toSearchableArray());

        $sphinxQuery = SphinxQL::create($this->connections)
            ->replace()
            ->into($index)
            ->columns($columns);

        $models->each(function ($model) use (&$sphinxQuery)
        {
            $sphinxQuery->values($model->toSearchableArray());
        });

        $sphinxQuery->execute();
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     *
     * @return void
     */
    public function delete($models)
    {
        if ($models->isEmpty())
        {
            return;
        }

        $model = $models->first();
        $index = $model->searchableAs();
        $key = $models->pluck($model->getKeyName())
            ->values()->all();

        SphinxQL::create($this->connections)
            ->delete()
            ->from($index)
            ->where('id', 'IN', $key)
            ->execute();
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->baseQuery($builder);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  int $perPage
     * @param  int $page
     *
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->baseQuery($builder, $perPage, $page);
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  mixed $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     *
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        return $results['results'];
    }

    /**
     * Map Get
     * @param  ResultSetInterface $results [description]
     * @param  Builder $builder [description]
     * @return [type]          [description]
     */
    public function mapGet($results, $builder)
    {
        $model = $builder->model;

        $key = collect($results->getStored())
            ->pluck($model->getKeyName())
            ->values()->all();

        $query = $model->whereIn($model->getKeyName(), $key);

        foreach ($builder->orders as $order)
        {
            $query = $query->orderBy(array_get($order, 'column'), array_get($order, 'direction'));
        }

        return $query->get();
    }

    /**
     * [mapIds description]
     * @param  [type] $results [description]
     * @return [type]          [description]
     */
    public function mapIds($results)
    {
        return collect($results->getStored())
            ->pluck('id')
            ->values()->all();
    }

    /**
     * [getTotalCount description]
     *
     * @author buchin@github.com
     *
     * @param  [type] $results [description]
     * @return [type]          [description]
     */
    public function getTotalCount($results)
    {
        return $results['count'];
    }

    /**
     * [get description]
     * @param  Builder $builder [description]
     * @return [type]           [description]
     */
    public function get(Builder $builder)
    {
        return Collection::make($this->mapGet(
            $this->search($builder), $builder
        ));
    }

    /**
     * Base query
     *
     * @author buchin@github.com, perdodi@github.com
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  int $perPage
     * @param  int $page
     *
     * @return mixed
     */
    protected function baseQuery(Builder $builder, $perPage = null, $page = null)
    {
        $model = $builder->model;
        $index = $model->searchableAs();
        $column_index = $model->toSearchableArray();

        if (array_key_exists('id', $column_index))
        {
            unset($column_index['id']);
        }

        $columns = array_keys($column_index);

        $query = SphinxQL::create($this->connections)
            ->select("*")
            ->from($index)
            ->match($columns, $builder->query);

        if (null !== $perPage && null !== $page)
        {
            $query = $query->limit(($page - 1) * $perPage, $perPage);
        }
        else
        {
            if ($limit = $builder->limit)
            {
                $query = $query->limit($limit);
            }

            return $query->execute();
        }

        $resultset = $query->execute();
        $result['results'] = $this->mapGet(
            $resultset, $builder
        );

        $meta = collect($query->enqueue(Helper::create($this->connections)->showMeta())->execute()->getStored());

        $meta->map(function ($item, $key) use (&$result)
        {
            $result['meta'][str_replace('[0]', '', $item['Variable_name'])] = $item['Value'];
        });

        $result['count'] = $result['meta']['total'];


        return $result;
    }

}
