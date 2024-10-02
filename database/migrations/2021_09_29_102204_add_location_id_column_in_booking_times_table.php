<?php

use App\User;
use App\Location;
use Carbon\Carbon;
use App\BookingTime;
use App\BusinessService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocationIdColumnInBookingTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        $location = Location::all();
        $locationCount = $location->count();

        if($locationCount > 0)
        {
            $location = Location::first()->id;

            Schema::table('booking_times', function (Blueprint $table) use ($location) {
                $table->unsignedInteger('location_id')->default($location)->after('company_id');
                $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');
            });

            Schema::table('employee_schedules', function (Blueprint $table) use ($location) {
                $table->unsignedInteger('location_id')->default($location)->after('company_id');
                $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');
            });

            $services = BusinessService::all()->unique('location_id');

            foreach($services as $service)
            {
                /* @phpstan-ignore-next-line */
                $bookingTime = BookingTime::where('company_id', $service->company_id)->where('location_id', $service->location_id)->first();

                if (is_null($bookingTime)) {
                    // seed booking times
                    /* @phpstan-ignore-next-line */
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
                            /* @phpstan-ignore-next-line */
                            'company_id' => $service->company_id,
                            /* @phpstan-ignore-next-line */
                            'location_id' => $service->location_id,
                            'day' => $weekday,
                            'start_time' => Carbon::parse('09:00:00', 'UTC'),
                            'end_time' => Carbon::parse('18:00:00', 'UTC'),
                        ];
                    }

                    BookingTime::insert($booking_times);
                }
            }

        }
        else
        {
            Schema::table('booking_times', function (Blueprint $table) {
                $table->unsignedInteger('location_id')->nullable()->after('company_id');
                $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');
            });

            Schema::table('employee_schedules', function (Blueprint $table) {
                $table->unsignedInteger('location_id')->nullable()->after('company_id');
                $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_times', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });

        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });

    }

}
