<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoomMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('meeting_id', 50)->nullable();
            $table->unsignedInteger('host_id')->nullable();
            $table->foreign('host_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('booking_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade')->onUpdate('cascade');
            $table->string('meeting_name', 100);
            $table->mediumText('description')->nullable();
            $table->dateTime('start_date_time');
            $table->dateTime('end_date_time');
            $table->boolean('host_video')->default(0);
            $table->string('start_link')->nullable();
            $table->string('join_link')->nullable();
            $table->enum('status', ['waiting', 'live', 'canceled', 'finished'])->default('waiting');
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('booking_type', ['online', 'offline'])->default('offline')->after('status');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->enum('approve_online_booking', ['active', 'inactive'])->default('inactive')->after('display_deal');
            $table->enum('approve_offline_booking', ['active', 'inactive'])->default('inactive')->after('approve_online_booking');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zoom_meetings');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_type');
        });
    }

}
