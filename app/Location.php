<?php

namespace App;

use App\Observers\LocationObserver;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Location
 *
 * @property int $id
 * @property string $name
 * @property int|null $country_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Country|null $country
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\BusinessService[] $services
 * @property-read int|null $services_count
 * @method static \Illuminate\Database\Eloquent\Builder|Location active()
 * @method static \Illuminate\Database\Eloquent\Builder|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property float $lat
 * @property float $lng
 * @property int|null $timezone_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\BookingTime[] $bookingTime
 * @property-read int|null $booking_time_count
 * @property-read \App\Deal|null $deal
 * @property-read \App\Timezone|null $timezone
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $user
 * @property-read int|null $user_count
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereTimezoneId($value)
 */
class Location extends Model
{
    protected $fillable = ['name', 'lat', 'lng', 'country_id', 'timezone_id'];

    protected static function boot()
    {
        parent::boot();
        static::observe(LocationObserver::class);
    }

    public function services()
    {
        return $this->hasMany(BusinessService::class);
    }

    public function deal()
    {
        return $this->hasOne(Deal::class);
    }

    public function bookingTime()
    {
        return $this->hasMany(BookingTime::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'location_user');
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }

} /* end of class */
