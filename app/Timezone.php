<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Timezone
 *
 * @property int $id
 * @property int|null $country_id
 * @property string $zone_name Timezone database name
 * @property string|null $name Timezone name
 * @property string|null $gmt_offset Timezone offset from UTC
 * @property string|null $gmt_offset_name Timezone offset from UTC name
 * @property string|null $abbreviation Timezone abbreviation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Country|null $country
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Location[] $locations
 * @property-read int|null $locations_count
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone query()
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereAbbreviation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereGmtOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereGmtOffsetName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timezone whereZoneName($value)
 * @mixin \Eloquent
 */
class Timezone extends Model
{
    protected $guarded = ['id'];
    protected $hidden = [ 'created_at', 'updated_at' ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

}
