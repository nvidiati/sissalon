<?php

namespace App;

use App\Scopes\CompanyScope;
use App\Observers\CategoryObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Category
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $image
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $category_image_url
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\BusinessService[] $services
 * @property-read int|null $services_count
 * @method static Builder|Category active()
 * @method static Builder|Category activeCompanyService()
 * @method static Builder|Category newModelQuery()
 * @method static Builder|Category newQuery()
 * @method static Builder|Category query()
 * @method static Builder|Category whereCreatedAt($value)
 * @method static Builder|Category whereId($value)
 * @method static Builder|Category whereImage($value)
 * @method static Builder|Category whereName($value)
 * @method static Builder|Category whereSlug($value)
 * @method static Builder|Category whereStatus($value)
 * @method static Builder|Category whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\BusinessService[] $posServices
 * @property-read int|null $pos_services_count
 */
class Category extends Model
{

    protected static function boot()
    {
        parent::boot();

        static::observe(CategoryObserver::class);
    }

    protected $fillable = ['name', 'slug', 'status', 'image'];

    protected $appends = [
        'category_image_url'
    ];

    public function getCategoryImageUrlAttribute()
    {
        if (is_null($this->image)) {
            return asset('img/no-image.jpg');
        }

        return asset_url('category/' . $this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeActiveCompanyService($query)
    {
        return $query->whereHas('services', function($q){
            $q->withoutGlobalScope(CompanyScope::class)->activeCompany();
        });
    }

    public function services()
    {
        return $this->hasMany(BusinessService::class)->withoutGlobalScope(CompanyScope::class);
    }

    public function posServices()
    {
        return $this->hasMany(BusinessService::class);
    }

}
