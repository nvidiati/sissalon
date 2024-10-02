<?php

namespace App;

use DateTime;
use Carbon\Carbon;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Observers\EmployeeScheduleObserver;

/**
 * App\EmployeeSchedule
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $is_working
 * @property \Illuminate\Support\Carbon|null $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property string $days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $employee
 * @method static Builder|EmployeeSchedule newModelQuery()
 * @method static Builder|EmployeeSchedule newQuery()
 * @method static Builder|EmployeeSchedule query()
 * @method static Builder|EmployeeSchedule whereCompanyId($value)
 * @method static Builder|EmployeeSchedule whereCreatedAt($value)
 * @method static Builder|EmployeeSchedule whereDays($value)
 * @method static Builder|EmployeeSchedule whereEmployeeId($value)
 * @method static Builder|EmployeeSchedule whereEndTime($value)
 * @method static Builder|EmployeeSchedule whereId($value)
 * @method static Builder|EmployeeSchedule whereIsWorking($value)
 * @method static Builder|EmployeeSchedule whereStartTime($value)
 * @method static Builder|EmployeeSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int|null $location_id
 * @property-read mixed $location_end_time
 * @property-read mixed $location_start_time
 * @property-read mixed $utc_end_time
 * @property-read mixed $utc_start_time
 * @property-read \App\Location|null $location
 * @method static Builder|EmployeeSchedule whereLocationId($value)
 */
class EmployeeSchedule extends Model
{

    protected static function boot()
    {
        parent::boot();

        static::observe(EmployeeScheduleObserver::class);

        $company = company();
        static::addGlobalScope(new CompanyScope);
    }

    protected $dates = ['start_time', 'end_time'];

    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function getStartTimeAttribute($value)
    {
        if($this->validateDate($value)){
            return Carbon::createFromFormat('H:i:s', $value)->setTimezone(Company::first()->timezone);
        }

        return '';
    }

    public function getEndTimeAttribute($value)
    {
        if($this->validateDate($value)){
            return Carbon::createFromFormat('H:i:s', $value)->setTimezone(Company::first()->timezone);
        }

        return '';
    }

    public function getLocationStartTimeAttribute()
    {
        /* @phpstan-ignore-next-line */
        return Carbon::createFromFormat('H:i:s', $this->attributes['start_time'])->setTimezone($this->location->timezone->zone_name);
    }

    public function getLocationEndTimeAttribute()
    {
        /* @phpstan-ignore-next-line */
        return Carbon::createFromFormat('H:i:s', $this->attributes['end_time'])->setTimezone($this->location->timezone->zone_name);
    }

    public function getUtcStartTimeAttribute()
    {
        return Carbon::createFromFormat('H:i:s', $this->attributes['start_time']);
    }

    public function getUtcEndTimeAttribute()
    {
        return Carbon::createFromFormat('H:i:s', $this->attributes['end_time']);
    }

    public function setStartTimeAttribute($value)
    {
        if(company())
        {
            $this->attributes['start_time'] = Carbon::parse($value, company()->timezone)->setTimezone('UTC')->format('H:i:s');
        }
        else
        {
            $this->attributes['start_time'] = Carbon::parse($value)->setTimezone('UTC')->format('H:i:s');
        }
    }

    public function setEndTimeAttribute($value)
    {
        if(company())
        {
            $this->attributes['end_time'] = Carbon::parse($value, company()->timezone)->setTimezone('UTC')->format('H:i:s');
        }
        else
        {
            $this->attributes['end_time'] = Carbon::parse($value)->setTimezone('UTC')->format('H:i:s');
        }
    }

    public function validateDate($format = 'H:i:s')
    {
        $d = DateTime::createFromFormat('H:i:s', $format);
        return $d && $d->format($format);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

}

