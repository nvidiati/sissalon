<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTwoColumnInGlobalSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->boolean('hide_cron_message')->after('rating_status')->default(0);
            $table->timestamp('last_cron_run')->after('hide_cron_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn('hide_cron_message');
            $table->dropColumn('last_cron_run');
        });
    }

}
