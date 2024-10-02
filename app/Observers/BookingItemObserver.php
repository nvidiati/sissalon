<?php

namespace App\Observers;

use App\Booking;
use App\BookingItem;
use App\BusinessService;
use App\Traits\ZoomSettings;
use App\User;
use App\ZoomMeeting;
use Auth;
use Carbon\Carbon;
use MacsiDigital\Zoom\Facades\Zoom;

class BookingItemObserver
{
    use ZoomSettings;

    public function creating(BookingItem $bookingItem)
    {

        if (company()) {
            $bookingItem->company_id = company()->id;
        }

        if($bookingItem->business_service_id) {

            $service = BusinessService::where('id', $bookingItem->business_service_id)->first();

            if($service->service_type === 'online')
            {
                $booking = Booking::with('users')->where('id', $bookingItem->booking_id)->first();
                $startTime = $booking->utc_date_time;
                $startDateTime = $booking->utc_date_time;  // Temperory start date time
                $endTime = '';

                if($service->time_type == 'days')
                {
                    $endTime = $startDateTime->addDays($service->time);
                }
                else if($service->time_type == 'hours')
                {
                    $endTime = $startDateTime->addHours($service->time);
                }
                else
                {
                    $endTime = $startDateTime->addMinutes($service->time);
                }

                $user = '';

                if($booking->source === 'pos')
                {
                    $user = User::withoutGlobalScopes()->findOrFail($booking->user_id);
                }
                else
                {
                    $user = Auth::user();
                }

                $hostId = $booking->users()->first()->id;
                $host = User::findOrFail($hostId);
                $startTime = $startTime->format('Y-m-d H:i:s');
                $endTime = $endTime->format('Y-m-d H:i:s');
                $this->setZoomConfigs();
                $meeting = new ZoomMeeting();
                $meeting->host_id = $host->id;
                /* @phpstan-ignore-next-line */
                $meeting->created_by = $user->id;
                $meeting->meeting_name = $service->name;
                $meeting->start_date_time = $startTime;
                $meeting->end_date_time = $endTime;
                $meeting->booking_id = $booking->id;
                $meeting->save();

                /* @phpstan-ignore-next-line */
                $user = Zoom::user()->find('me');

                $meetingEndDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $meeting->end_date_time);

                $commonSettings = [
                    'type' => 2,
                    'topic' => $service->name,
                    'start_time' => $meeting->start_date_time,
                    'duration' => $meetingEndDateTime->diffInMinutes($meeting->start_date_time),
                    'timezone' => 'UTC',
                    'agenda' => $booking->additional_notes,
                    'alternative_host' => [$host->email],
                    'settings' => [
                        'host_video' => true,
                        'participant_video' => true,
                    ]
                ];

                $zoomMeeting = $user->meetings()->make($commonSettings);
                $savedMeeting = $user->meetings()->save($zoomMeeting);

                $meeting->meeting_id = strval($savedMeeting->id);
                $meeting->start_link = $savedMeeting->start_url;
                $meeting->join_link = $savedMeeting->join_url;
                $meeting->password = $savedMeeting->password;
                $meeting->save();

            }
        }



    }

}
