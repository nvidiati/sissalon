<?php

namespace App\Observers;

use App\Helper\SearchLog;
use App\Category;
use Illuminate\Support\Facades\File;

class CategoryObserver
{

    public function created(Category $category)
    {
        SearchLog::createSearchEntry($category->id, 'Category', $category->name, 'superadmin.categories.edit');

    }

    public function updating(Category $category)
    {
        SearchLog::updateSearchEntry($category->id, 'Category', $category->name, 'superadmin.categories.edit');
    }

    public function deleted(Category $category)
    {
        if(!is_null($category->getRawOriginal('image')))
        {
            $path = public_path('user-uploads/category/'.$category->getRawOriginal('image'));

            if($path) {
                File::delete($path);
            }

        }

        SearchLog::deleteSearchEntry($category->id, 'superadmin.categories.edit');
    }

}
