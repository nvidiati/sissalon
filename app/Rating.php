<?php

namespace App;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Rating
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $booking_id
 * @property int|null $user_id
 * @property int|null $service_id
 * @property int|null $deal_id
 * @property int|null $product_id
 * @property string|null $rating
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Booking|null $booking
 * @property-read \App\Company|null $company
 * @property-read \App\Deal|null $deal
 * @property-read \App\Product|null $product
 * @property-read \App\BusinessService|null $service
 * @method static \Illuminate\Database\Eloquent\Builder|Rating active()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereDealId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereUserId($value)
 * @mixin \Eloquent
 */
class Rating extends Model
{

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function service()
    {
        return $this->belongsTo(BusinessService::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
