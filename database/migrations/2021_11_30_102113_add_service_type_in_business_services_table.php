<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceTypeInBusinessServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->enum('service_type', ['online', 'offline'])->default('offline')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::table('business_services', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }

}
