<?php

namespace Webkul\UI\DataGrid\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;

trait DatagridHelper
{

    /**
     * Add the index as alias of the column and use the column to make things happen
     *
     * @param string $alias
     * @param string $column
     *
     * @return void
     */
    public function addFilter($alias, $column)
    {
        $this->filterMap[$alias] = $column;

        $this->enableFilterMap = true;
    }
    
    /**
     * @param string $name
     *
     * @return void
     */
    protected function fireEvent($name)
    {
        if (isset($name)) {
            $className = get_class($this->invoker);

            $className = last(explode("\\", $className));

            $className = strtolower($className);

            $eventName = $className . '.' . $name;

            Event::dispatch($eventName, $this->invoker);
        }
    }

    /**
     * Parse the URL and get it ready to be used.
     *
     * @return void
     */
    public function parseUrl()
    {
        $parsedUrl = [];
        $unparsed = url()->full();

        $route = request()->route() ? request()->route()->getName() : "";

        if (count(explode('?', $unparsed)) > 1) {
            $to_be_parsed = explode('?', $unparsed)[1];

            parse_str($to_be_parsed, $parsedUrl);
            unset($parsedUrl['page']);
        }

        if (isset($parsedUrl['grand_total'])) {
            foreach ($parsedUrl['grand_total'] as $key => $value) {
                $parsedUrl['grand_total'][$key] = str_replace(',', '.', $parsedUrl['grand_total'][$key]);
            }           
        }

        $this->itemsPerPage = isset($parsedUrl['perPage']) ? $parsedUrl['perPage']['eq'] : $this->itemsPerPage;

        unset($parsedUrl['perPage']);

        foreach ($parsedUrl as $key => $value) {
            if (! is_array($value)) {
                unset($parsedUrl[$key]);
            }
        }

        return $parsedUrl;
    }

    /**
     * To find the alias of the column and by taking the column name.
     *
     * @param array $columnAlias
     *
     * @return array
     */
    public function findColumnType($columnAlias)
    {
        foreach ($this->completeColumnDetails as $column) {
            if ($column['index'] == $columnAlias) {
                return [$column['type'], $column['index']];
            }
        }
    }

    /**
     * Prepare column filtered values.
     *
     * @return collection
     */
    public function attachColumnValues($columnName, $info)
    {
        foreach ($this->completeColumnDetails as $index => $column) {
            if ($column['index'] == $columnName) {
                $this->completeColumnDetails[$index]['values'] = explode(',', array_values($info)[0]);
            }
        }

        return $this->completeColumnDetails;
    }

    /**
     * Prepare tab filters.
     *
     * @return array
     */
    public function prepareTabFilters($key)
    {
        $tabFilters = config("datagrid_filters")[$key] ?? [];
        
        foreach ($tabFilters as $tabIndex => $filter) {
            if (($filter['value_type'] ?? false) == "lookup") {
                $values = app($filter['repositoryClass'])
                            ->get(['name', 'code as key', \DB::raw("false as isActive")])
                            ->prepend([
                                'isActive'  => true,
                                'key'       => 'all',
                                'name'      => trans('admin::app.datagrid.all'),
                            ])
                            ->toArray();
                            
                $tabFilters[$tabIndex]['values'] = $values;
            } else {
                foreach ($filter['values'] as $valueIndex => $value) {
                    $tabFilters[$tabIndex]['values'][$valueIndex]['name'] = trans($tabFilters[$tabIndex]['values'][$valueIndex]['name']);
                }
            }
        }
        
        return $tabFilters;
    }
}