<?php

namespace App\Helper;

use App\UniversalSearch;
use Illuminate\Support\Arr;

class SearchLog
{

    public static function createSearchEntry($searchableId, $type, $title, $route, $companyId = null)
    {
        $search = new UniversalSearch();

        $search->searchable_id = $searchableId;
        $search->searchable_type = $type;
        $search->title = $title;
        $search->route_name = $route;
        $search->company_id = $companyId;

        $search->save();
    }

    public static function updateSearchEntry($searchableId, $type, $title, $route, $data = null)
    {
        $search = UniversalSearch::where(['searchable_id' => $searchableId, 'route_name' => $route]);

        if ($data && !Arr::has($data, 'modified')) {
            $search = $search->where('title', current($data));
        }

        $search = $search->first();

        if($search != null)
        {
            $search->searchable_id = $searchableId;
            $search->searchable_type = $type;
            $search->title = $title;
            $search->route_name = $route;

            if ($data && Arr::has($data, ['modified'])) {
                $value = $data['modified'];

                $search->searchable_id = $value['searchable_id'];
                $search->title = $value['title'];
            }

            $search->save();
        }

    }

    public static function deleteSearchEntry($searchableId, $route)
    {
        $searches = UniversalSearch::where(['searchable_id' => $searchableId, 'route_name' => $route])->get();

        foreach ($searches as $search) {
            $search->delete();
        }
    }

}
