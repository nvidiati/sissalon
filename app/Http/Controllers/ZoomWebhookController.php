<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Traits\ZoomSettings;
use App\ZoomMeeting;
use Illuminate\Http\Request;

class ZoomWebhookController extends Controller
{
    Use ZoomSettings;

    public function index()
    {
        $this->setZoomConfigs();
        $response = request()->all();
        \Log::debug('response = ', request()->all());
        $event = $response['event'];

        switch ($event)
        {
        case 'meeting.started'  :
            $this->meetingStarted($response);
            break;

        case 'meeting.ended':
            $this->meetingEnded($response);
            break;

        /* case 'meeting.deleted':
            $this->meetingDeleted($response);
            break;

        case 'meeting.created':
            $this->meetingCreated($response);
            break;

        case 'meeting.updated':
            $this->meetingUpdated($response);
            break; */
        default:
            //
            break;
        }

        return response('Webhook Handled', 200);
    }

    public function meetingStarted($response)
    {
        $meetingId = $response['payload']['object']['id'];
        $meeting = ZoomMeeting::where('meeting_id', $meetingId)->first();
        $booking = Booking::findOrFail($meeting->booking_id);

        if ($meeting)
        {
            $meeting->status = 'live';
            $meeting->save();

            $booking->status = 'in progress';
            $booking->update();
        }
    }

    public function meetingEnded($response)
    {
        $meetingId = $response['payload']['object']['id'];
        $meeting = ZoomMeeting::where('meeting_id', $meetingId)->first();
        $booking = Booking::findOrFail($meeting->booking_id);

        if ($meeting)
        {
            $meeting->status = 'finished';
            $meeting->save();

            $booking->status = 'completed';
            $booking->update();
        }
    }

}
