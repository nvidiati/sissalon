<?php

use App\PackageModules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoomPackageModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        $default_modules = new PackageModules();
        $default_modules->name = 'Zoom Meeting';
        $default_modules->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {

    }

}
