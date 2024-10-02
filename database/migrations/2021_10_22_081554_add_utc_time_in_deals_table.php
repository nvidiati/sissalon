<?php

use App\Deal;
use App\Timezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUtcTimeInDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('deals', function () {

            $deals = Deal::with(['location', 'location.timezone'])->get();

            foreach($deals as $deal)
            {
                $timezone = Timezone::find($deal->location->timezone_id);

                if($timezone != '')
                {
                    $deal_timezone = $timezone->zone_name;
                    $start_date_time = Carbon::parse($deal->start_date_time, $deal_timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $deal->start_date_time = $start_date_time;
                    $end_date_time = Carbon::parse($deal->end_date_time, $deal_timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $deal->end_date_time = $end_date_time;
                    $open_time = Carbon::parse($deal->open_time, $deal_timezone)->setTimezone('UTC')->format('H:i:s');
                    $deal->open_time = $open_time;
                    $close_time = Carbon::parse($deal->close_time, $deal_timezone)->setTimezone('UTC')->format('H:i:s');
                    $deal->close_time = $close_time;
                    $deal->save();
                }
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            //
        });
    }

}
