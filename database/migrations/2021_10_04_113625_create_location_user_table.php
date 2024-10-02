<?php

use App\Location;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('location_user', function (Blueprint $table) {
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('user_id');

            $table->foreign('location_id')->references('id')->on('locations')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['location_id', 'user_id']);
        });

        $location = Location::all();
        $locationCount = $location->count();

        if($locationCount > 0)
        {
            $location = Location::first()->id;
            $users = User::otherThanCustomers()->get();

            foreach($users as $user)
            {
                $user->location()->attach($location);
            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_user');
    }

}
