<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ZoomMeeting
 *
 * @property int $id
 * @property string|null $meeting_id
 * @property int|null $host_id
 * @property int|null $created_by
 * @property int $booking_id
 * @property string $meeting_name
 * @property string|null $description
 * @property string $start_date_time
 * @property string $end_date_time
 * @property int $host_video
 * @property string|null $start_link
 * @property string|null $join_link
 * @property string $status
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting query()
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereEndDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereHostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereHostVideo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereJoinLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereMeetingName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereStartDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereStartLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomMeeting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ZoomMeeting extends Model
{

}
