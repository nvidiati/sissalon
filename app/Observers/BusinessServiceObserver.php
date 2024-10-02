<?php

namespace App\Observers;

use App\Company;
use Carbon\Carbon;
use App\BookingTime;
use App\BusinessService;
use App\Helper\SearchLog;
use App\Location;
use Illuminate\Support\Facades\File;

class BusinessServiceObserver
{

    public function creating(BusinessService $service)
    {
        if (company()) {
            $service->company_id = company()->id;
        }
    }

    public function created(BusinessService $service)
    {
        if (company()) {
            $companySetting = company();
        }
        else{
            $companySetting = Company::first();
        }

        SearchLog::createSearchEntry($service->id, 'Service', $service->name, 'admin.business-services.edit', $service->company_id);

        $bookingTime = BookingTime::where('company_id', $companySetting->id)->where('location_id', $service->location_id)->first();

        if (is_null($bookingTime)) {
            // seed booking times
            $location = Location::with('timezone')->where('id', $service->location_id)->first();
            $booking_times = [];
            $weekdays = [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ];

            foreach ($weekdays as $weekday) {
                $booking_times[] = [
                    'company_id' => $companySetting->id,
                    'location_id' => $service->location_id,
                    'day' => $weekday,
                    'start_time' => Carbon::parse('09:00:00', $location->timezone->zone_name)->setTimezone('UTC'),
                    'end_time' => Carbon::parse('18:00:00', $location->timezone->zone_name)->setTimezone('UTC'),
                ];
            }

            BookingTime::insert($booking_times);
        }

    }

    public function updating(BusinessService $service)
    {
        if (company()) {
            $companySetting = company();
        }
        else{
            $companySetting = Company::first();
        }

        SearchLog::updateSearchEntry($service->id, 'Service', $service->name, 'admin.business-services.edit');

        $currentLocationId = BusinessService::findOrFail($service->id)->location_id;

        $serviceCount = BusinessService::where('location_id', $currentLocationId)->where('company_id', $companySetting->id)->get();

        if($serviceCount->count() == 1)
        {
            $bookingTimes = BookingTime::where('location_id', $currentLocationId)->where('company_id', $companySetting->id)->get();

            if($bookingTimes->count() > 0)
            {
                foreach($bookingTimes as $bookingTime)
                {
                    $bookingTime->delete();
                }

                $bookingTime = BookingTime::where('company_id', $companySetting->id)->where('location_id', $service->location_id)->first();

                if (is_null($bookingTime)) {
                    // seed booking times
                    $location = Location::with('timezone')->where('id', $service->location_id)->first();
                    $booking_times = [];
                    $weekdays = [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ];

                    foreach ($weekdays as $weekday) {
                        $booking_times[] = [
                            'company_id' => $companySetting->id,
                            'location_id' => $service->location_id,
                            'day' => $weekday,
                            'start_time' => Carbon::parse('09:00:00', $location->timezone->zone_name)->setTimezone('UTC'),
                            'end_time' => Carbon::parse('18:00:00', $location->timezone->zone_name)->setTimezone('UTC'),
                        ];
                    }

                    BookingTime::insert($booking_times);
                }
            }
        }

    }

    public function deleted(BusinessService $service)
    {
        SearchLog::deleteSearchEntry($service->id, 'admin.business-services.edit');

        // delete images folder from user-uploads/service directory
        File::deleteDirectory(public_path('user-uploads/service/'.$service->id));
        $serviceCount = BusinessService::where('location_id', $service->location_id)->where('company_id', $service->location_id)->get();

        if($serviceCount->count() == 0)
        {
            $bookingTimes = BookingTime::where('location_id', $service->location_id)->where('company_id', $service->location_id)->get();

            if($bookingTimes->count() > 0)
            {
                foreach($bookingTimes as $bookingTime)
                {
                    $bookingTime->delete();
                }
            }
        }

    }

}
